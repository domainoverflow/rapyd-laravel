<?php

namespace Zofe\Rapyd\DataFilter;

use Zofe\Rapyd\DataForm\DataForm;
use Zofe\Rapyd\Session;
use Illuminate\Support\Facades\Form;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;

class DataFilter extends DataForm
{

    public $cid;
    public $source;
    
    protected $process_url = '';
    protected $reset_url = '';
    
    /**
     *
     * @var \Illuminate\Database\Query\Builder
     */
    public $query;

    /**
     * @param $source
     *
     * @return static
     */
    public static function source($source = null)
    {
        $ins = new static;
        $ins->source = $source;
        $ins->cid = $ins->getIdentifier();
        return $ins;
    }

    protected function table($table)
    {
        $this->query = DB::table($table);
        return $this->query;
    }

        
    protected function sniffAction()
    {

        $this->reset_url = $this->url->remove('ALL')->append('reset', 1)->get();
        $this->process_url =  $this->url->remove('ALL')->append('search', 1)->get();
        
        ///// search /////
        if ($this->url->value('search')) {
            $this->action = "search";
            // persistence
            Session::savePersistence();
        }
        ///// reset /////
        elseif ($this->url->value("reset")) {
            $this->action = "reset";
            // persistence cleanup
            Session::clearPersistence();
        }
        ///// show /////
        else {
            $page = Session::getPersistence();
            if (count($page)) {
                $this->action = "search";
            }
            // persistence
            Session::savePersistence();
        }
    }
    
    protected function process()
    {
        //database save
        switch ($this->action) {
            case "search":
                // prepare the WHERE clause
                foreach ($this->fields as $field) {
                    if ($field->value != "") {
                        if (strpos($field->name, "_copy") > 0) {
                            $name = substr($field->db_name, 0, strpos($field->db_name, "_copy"));
                        } else {
                            $name = $field->db_name;
                        }
                        $field->get_value();
                        $field->get_new_value();
                        $value = $field->new_value;
                        switch ($field->clause) {
                            case "like":
                                $this->query = $this->query->where($name, 'LIKE', '%'.$value.'%');
                                break;
                            case "orlike":
                                $this->query = $this->query->orWhere($name, 'LIKE', '%'.$value.'%');
                                break;
                            case "where":
                                $this->db->where($name . $field->operator, $value);
                                break;
                            case "orwhere":
                                $this->db->orWhere($name . $field->operator, $value);
                                break;
                        }
                    }
                }
            case "reset":
                $this->process_status = "show";
                return true;
                break;
            default:
                return false;
                
        }
    }
    
}