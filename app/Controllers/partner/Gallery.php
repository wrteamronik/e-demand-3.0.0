<?php
namespace App\Controllers\partner;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;
class Gallery extends Partner
{
    public $partner, $validations, $db;
    public function __construct()
    {
        parent::__construct();
        $this->db      = \Config\Database::connect();
    }
    public function index()
    {
        if (!$this->isLoggedIn) {
            return redirect('partner/login');
        }
        $user_id = $this->ionAuth->user()->row()->id;
        setPageInfo($this->data, 'Gallery | Provider Panel', 'gallery');
        $directoryPaths = [
            FCPATH . '/public/uploads',
            FCPATH . '/public/backend/assets'
        ];
        $not_allowed_folders = [
            'mpdf', 'tools', 'css', 'fonts', 'js', 'categories', 'chat_attachement', 'languages', 'ratings', 'media', 'notification',
            'offers', 'provider_bulk_upload', 'site', 'sliders', 'users',
            'img', 'images', 'web_settings', 'chat_attachment', 'promocodes', 'provider_bulk_file', 'service_bulk_upload'
        ];
        $partner = fetch_details('partner_details', ['partner_id' => $user_id], ['banner', 'national_id', 'passport', 'address_id']);
        $services = fetch_details('services', ['user_id' => $user_id], ['image', 'other_images', 'files']);
        $orders = fetch_details('orders', ['partner_id' => $user_id], ['work_started_proof', 'work_completed_proof']);
        // Get partner files
        $partnerFiles = [];
        $fields = [
            'partner_details' => ['banner', 'national_id', 'passport', 'address_id'],
            'services' => ['image', 'other_images', 'files'],
            'orders' => ['work_started_proof', 'work_completed_proof']
        ];
        foreach ($fields as $type => $typeFields) {
            $data = $type === 'partner_details' ? [$partner[0]] : ($$type ?? []);
            foreach ($data as $item) {
                foreach ($typeFields as $field) {
                    if (!empty($item[$field])) {
                        $files = json_decode($item[$field], true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $files = explode(',', $item[$field]);
                        }
                        $partnerFiles = array_merge($partnerFiles, $files);
                    }
                }
            }
        }
        $partnerFiles = array_unique(array_filter($partnerFiles, 'strlen'));
        // Get folder data
        $folderData = [];
        foreach ($directoryPaths as $directoryPath) {
            $folders = array_filter(glob($directoryPath . '/*'), 'is_dir');
            foreach ($folders as $folder) {
                $publicPos = strpos($folder, '/public/');
                $pathIncludingPublic = $publicPos !== false ? substr($folder, $publicPos) : $folder;
                $folderName = basename($folder);
                if (!in_array($folderName, $not_allowed_folders)) {
                    $files = glob($folder . '/*');
                    $normalizedPartnerFiles = array_map(function ($file) {
                        return ltrim(str_replace(FCPATH, '', $file), '/');
                    }, $partnerFiles);
                    $partnerFolderFiles = array_filter($files, function ($file) use ($normalizedPartnerFiles) {
                        $normalizedFile = ltrim(str_replace(FCPATH, '', $file), '/');
                        return in_array($normalizedFile, $normalizedPartnerFiles) && file_exists($file) && !preg_match('/\.(txt|html)$/i', $file);
                    });
                    $fileCount = count($partnerFolderFiles);
                    if ($fileCount > 0) {
                        $folderData[] = [
                            'name' => $folderName,
                            'path' => $pathIncludingPublic,
                            'file_count' => $fileCount
                        ];
                    }
                }
            }
        }
        $this->data['folders'] = $folderData;
        $this->data['users'] = fetch_details('users', ['id' => $user_id], ['company']);
        return view('backend/partner/template', $this->data);
    }
    public function GetGallaryFiles()
    {
        // Ensure the user is logged in
        if (!$this->isLoggedIn) {
            return redirect('partner/login');
        }
        $user_id = $this->ionAuth->user()->row()->id;
        $uri = service('uri');
        $segments = $uri->getSegments();
        $new_path = implode('/', array_slice($segments, array_search('get-gallery-files', $segments) + 1));
        $folderPath = rtrim(FCPATH, '/') . '/' . $new_path;
        $files = glob($folderPath . '/*');
        $details = fetch_details('services', ['user_id' => $user_id], ['image', 'other_images', 'files']);
        $orders = fetch_details('orders', ['partner_id' => $user_id], ['work_started_proof', 'work_completed_proof']);
        $details = array_merge($details, $orders);
        $getFileNames = function ($field) use ($details) {
            return array_reduce($details, function ($carry, $item) use ($field) {
                if (!empty($item[$field])) {
                    $files = json_decode($item[$field], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $files = explode(',', $item[$field]);
                    }
                    $carry = array_merge($carry, array_map('basename', $files));
                }
                return $carry;
            }, []);
        };
        $allFiles = array_unique(array_filter(array_merge(
            $getFileNames('image'),
            $getFileNames('other_images'),
            $getFileNames('files'),
            $getFileNames('national_id'),
            $getFileNames('address_id'),
            $getFileNames('banner'),
            $getFileNames('passport'),
            $getFileNames('work_started_proof'),
            $getFileNames('work_completed_proof'),
        ), 'strlen'));
        $filesData = array_map(function ($file) use ($new_path, $allFiles) {
            $fileInfo = pathinfo($file);
            $fileName = $fileInfo['basename'];
            if (in_array($fileName, $allFiles) && mime_content_type($file) != "text/html") {
                return [
                    'name' => $fileName,
                    'type' => mime_content_type($file),
                    'size' => $this->formatFileSize(filesize($file)),
                    'full_path' => base_url() . '/' . $new_path . '/' . $fileName,
                    'path' => $new_path . '/' . $fileName,
                ];
            }
        }, $files);
        $this->data['files'] = array_filter($filesData);
        $this->data['folder_name'] = end($segments);
        $this->data['total_files'] = count($this->data['files']);
        $this->data['path'] = $new_path;
        setPageInfo($this->data, 'Gallery-' . $this->data['folder_name'] . ' | Provider Panel', 'gallery_files');
        return view('backend/partner/template', $this->data);
    }
    private function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    public function downloadAll()
    {
        if (!$this->isLoggedIn) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }
        $folder = $this->request->getPost('folder');
        $full_path = $this->request->getPost('full_path');
        $folderPath = FCPATH . $full_path;
        if (!is_dir($folderPath) || strpos(realpath($folderPath), FCPATH) !== 0) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid folder']);
        }
        $zipName = $folder . '.zip';
        $zipPath = FCPATH . 'public/uploads/' . $zipName;
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($folderPath),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($folderPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
            $zip->close();
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipName . '"');
            header('Content-Length: ' . filesize($zipPath));
            header('Pragma: no-cache');
            header('Expires: 0');
            readfile($zipPath);
            unlink($zipPath);
            exit;
        } else {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Could not create zip file']);
        }
    }
}
