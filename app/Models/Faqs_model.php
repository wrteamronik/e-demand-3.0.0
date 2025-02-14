<?php
namespace App\Models;
use CodeIgniter\Model;
class Faqs_model extends Model
{
    protected $DBGroup = 'default';
    protected $table = ' faqs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['english_question', 'russian_question', 'estonian_question', 'english_answer', 'russian_answer', 'estonian_answer', 'status'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
    public $base, $admin_id, $db;
    public function list($from_app = false, $search = '', $limit = 10, $offset = 0, $sort = 'id', $order = 'ASC')
    {
        $db      = \Config\Database::connect();
        $builder = $db->table('faqs');
        if (isset($search) and $search != '') {
            $multipleWhere = ['`id`' => $search, '`english_question`' => $search, '`russian_question`' => $search, '`estonian_question`' => $search, '`english_answer`' => $search, '`russian_answer`' => $search, '`estonian_answer`' => $search, '`status`' => $search];
        }
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $builder->orWhere($multipleWhere);
        }
        $Faq_count = $builder->select(' COUNT(id) as `total` ')->get()->getResultArray();
        $total = $Faq_count[0]['total'];
        $builder->select();
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $builder->orLike($multipleWhere);
        }
        $faq_record = $builder->orderBy($sort, $order)->limit($limit, $offset)->get()->getResultArray();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        foreach ($faq_record as $row) {
            $tempRow['id'] = $row['id'];
            
            $tempRow['english_question'] = $row['english_question'];
            $tempRow['russian_question'] = $row['russian_question'];
            $tempRow['estonian_question'] = $row['estonian_question'];

            $tempRow['english_answer'] = $row['english_answer'];
            $tempRow['russian_answer'] = $row['russian_answer'];
            $tempRow['estonian_answer'] = $row['estonian_answer'];

            $tempRow['status'] = $row['status'];
            $tempRow['created_at'] =format_date( $row['created_at'],'d-m-Y');
            $rows[] = $tempRow;
        }
        if ($from_app) {
            $data['total'] = (empty($total)) ? (string) count($rows) : $total;
            $data['data'] = $rows;
            return $data;
        } else {
            $bulkData['rows'] = $rows;
            return json_encode($bulkData);
        }
    }
}
