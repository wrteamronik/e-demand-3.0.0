<?php

namespace App\Controllers\admin;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class Gallery extends Admin
{
    public $category,  $validation;
    public function __construct()
    {
        parent::__construct();
        $this->validation = \Config\Services::validation();
        $this->superadmin = $this->session->get('email');
        helper('ResponceServices');
    }
    public function index()
    {
        if (!$this->isLoggedIn || !$this->userIsAdmin) {
            return redirect('admin/login');
        }
        $directoryPaths = [
            FCPATH . '/public/uploads',
            FCPATH . '/public/backend/assets'
        ];
        $not_allowed_folders = ['mpdf', 'tools', 'css', 'fonts', 'js'];
        $folderData = [];
        foreach ($directoryPaths as $directoryPath) {
            $folders = array_filter(glob($directoryPath . '/*'), 'is_dir');
            foreach ($folders as $folder) {
                $publicPos = strpos($folder, '/public/');
                if ($publicPos !== false) {
                    $pathIncludingPublic = substr($folder, $publicPos);
                } else {
                    $pathIncludingPublic = $folder;
                }
                $folderName = basename($folder);
                if (!in_array($folderName, $not_allowed_folders)) {
                    $files = glob($folder . '/*');

                    $files = array_filter($files, function ($file) {
                        return !preg_match('/\.(txt|html)$/i', $file);
                    });
                    $fileCount = count($files);
                    $folderData[] = [
                        'name' => $folderName,
                        'fileCount' => $fileCount,
                        'path' => $pathIncludingPublic
                    ];
                }
            }
        }
        $this->data['folders'] = $folderData;
        setPageInfo($this->data, 'Gallery | Admin Panel', 'gallery');
        return view('backend/admin/template', $this->data);
    }
    public function GetGallaryFiles()
    {
        if (!$this->isLoggedIn || !$this->userIsAdmin) {
            return redirect('admin/login');
        }
        $uri = service('uri');
        $segments = $uri->getSegments();
        $galleryIndex = array_search('get-gallery-files', $segments);
        if ($galleryIndex !== false && $galleryIndex + 1 < count($segments)) {
            $new_path = implode('/', array_slice($segments, $galleryIndex + 1));
        } else {
            $new_path = '';
        }
        if (count($segments) >= 3) {
            $folder_name = end($segments);
            $basePath = FCPATH;
            $folderPath = rtrim($basePath, '/') . '/' . $new_path;
            $files = glob($folderPath . '/*');
            $filesData = [];
            foreach ($files as $file) {
                $fileInfo = pathinfo($file);
                $fileType = mime_content_type($file);

                $fileSize = filesize($file);
                $folderName = basename($file);
                $fullPath = base_url() . '/' . $new_path . '/' . $folderName;
                $servicePath = $new_path . '/' . $folderName;

                if ($fileType != "text/html") {
                    $filesData[] = [
                        'name' => $fileInfo['basename'],
                        'type' => $fileType,
                        'size' => $this->formatFileSize($fileSize),
                        'full_path' => $fullPath,
                        'path' => $servicePath,
                    ];
                }
            }
        }
        $this->data['files'] = $filesData;
        $this->data['folder_name'] = $folder_name;
        $this->data['total_files'] = count($filesData);
        $this->data['path'] = ($new_path);
        setPageInfo($this->data, 'Gallery-' . $folder_name . ' | Admin Panel', 'gallery_files');
        return view('backend/admin/template', $this->data);
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
        if (!$this->isLoggedIn || !$this->userIsAdmin) {
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
