<?php


if (!defined('BASEPATH')) { exit('No direct script access allowed'); }

class Hr extends CI_Controller {
    
  
    public function __construct() {
        parent::__construct();
        setUserContext($this);
        $this->load->model('leaves_model');
        $this->lang->load('hr', $this->language);
        $this->lang->load('global', $this->language);
    }
    
   
    public function employees() {
        $this->auth->checkIfOperationIsAllowed('list_employees');
        $data = getUserContext($this);
        $this->lang->load('datatable', $this->language);
        $data['title'] = lang('hr_employees_title');
        $data['help'] = $this->help->create_help_link('global_link_doc_page_list_employees');
        $data['flash_partial_view'] = $this->load->view('templates/flash', $data, TRUE);
        $this->load->view('templates/header', $data);
        $this->load->view('menu/index', $data);
        $this->load->view('hr/employees', $data);
        $this->load->view('templates/footer');
    }
    
  
    public function employeesOfEntity($id = 0, $children = TRUE) {
        header("Content-Type: application/json");
        if ($this->auth->isAllowed('list_employees') == FALSE) {
            $this->output->set_header("HTTP/1.1 403 Forbidden");
        } else {
            $children = filter_var($children, FILTER_VALIDATE_BOOLEAN);
            $this->load->model('users_model');
            $employees = $this->users_model->employeesOfEntity($id, $children);
            $msg = '{"iTotalRecords":' . count($employees);
            $msg .= ',"iTotalDisplayRecords":' . count($employees);
            $msg .= ',"aaData":[';
            foreach ($employees as $employee) {
                $msg .= '["' . $employee->id . '",';
                $msg .= '"' . $employee->firstname . '",';
                $msg .= '"' . $employee->lastname . '",';
                $msg .= '"' . $employee->email . '",';
                $msg .= '"' . $employee->entity . '",';
                $msg .= '"' . $employee->contract . '",';
                $msg .= '"' . $employee->manager_name . '"';
                $msg .= '],';
            }
            $msg = rtrim($msg, ",");
            $msg .= ']}';
            echo $msg;
        }
    }

  
    public function leaves($id) {
        $this->auth->checkIfOperationIsAllowed('list_employees');
        $data = getUserContext($this);
        $this->load->model('users_model');
        $data['name'] = $this->users_model->getName($id);
       
        if ($data['name'] == "") {
            redirect('notfound');
        }
        $this->lang->load('datatable', $this->language);
        $data['title'] = lang('hr_leaves_title');
        $data['user_id'] = $id;
        $this->load->model('leaves_model');
        $data['leaves'] = $this->leaves_model->getLeavesOfEmployee($id);
        $data['flash_partial_view'] = $this->load->view('templates/flash', $data, TRUE);
        $this->load->view('templates/header', $data);
        $this->load->view('menu/index', $data);
        $this->load->view('hr/leaves', $data);
        $this->load->view('templates/footer');
    }
    

    public function overtime($id) {
        $this->auth->checkIfOperationIsAllowed('list_employees');
        $data = getUserContext($this);
        $this->load->model('users_model');
        $data['name'] = $this->users_model->getName($id);
      
        if ($data['name'] == "") {
            redirect('notfound');
        }
        $this->lang->load('datatable', $this->language);
        $data['title'] = lang('hr_overtime_title');
        $data['user_id'] = $id;
        $this->load->model('overtime_model');
        $data['extras'] = $this->overtime_model->getExtrasOfEmployee($id);
        $data['flash_partial_view'] = $this->load->view('templates/flash', $data, TRUE);
        $this->load->view('templates/header', $data);
        $this->load->view('menu/index', $data);
        $this->load->view('hr/overtime', $data);
        $this->load->view('templates/footer');
    }
    
  
    public function counters($source, $id, $refTmp = NULL) {
        if ($source == 'collaborators') { $this->auth->checkIfOperationIsAllowed('list_collaborators'); }
        if ($source == 'employees') { $this->auth->checkIfOperationIsAllowed('list_employees'); }
        $data = getUserContext($this);
        if ($source == 'collaborators') { $data['source'] = 'collaborators'; }
        if ($source == 'employees') { $data['source'] = 'employees'; }
        $this->lang->load('entitleddays', $this->language);
        $this->lang->load('datatable', $this->language);
        $refDate = date("Y-m-d");
        if ($refTmp != NULL) {
            $refDate = date("Y-m-d", $refTmp);
            $data['isDefault'] = 0;
        } else {
            $data['isDefault'] = 1;
        }
        
        $data['refDate'] = $refDate;
        $data['summary'] = $this->leaves_model->getLeaveBalanceForEmployee($id, FALSE, $refDate);
        if (!is_null($data['summary'])) {
            $this->load->model('entitleddays_model');
            $this->load->model('users_model');
            $user = $this->users_model->getUsers($id);
            $data['employee_name'] = $user['firstname'] . ' ' . $user['lastname'];
            $this->load->model('contracts_model');
            $contract = $this->contracts_model->getContracts($user['contract']); 
            $data['contract_name'] = $contract['name'];
            $data['contract_start'] = $contract['startentdate'];
            $data['contract_end'] = $contract['endentdate'];
            $data['employee_id'] = $id;
            $data['contract_id'] = $user['contract'];
            $data['entitleddayscontract'] = $this->entitleddays_model->getEntitledDaysForContract($user['contract']);
            $data['entitleddaysemployee'] = $this->entitleddays_model->getEntitledDaysForEmployee($id);
            $data['title'] = lang('hr_summary_title');
            $data['help'] = $this->help->create_help_link('global_link_doc_page_leave_balance_employee');
            $this->load->view('templates/header', $data);
            $this->load->view('menu/index', $data);
            $this->load->view('hr/counters', $data);
            $this->load->view('templates/footer');
        } else {
            $this->session->set_flashdata('msg', lang('hr_summary_flash_msg_error'));
            redirect('hr/employees');
        }
    }
    
   
    public function createleave($id) {
        $this->auth->checkIfOperationIsAllowed('list_employees');
        $data = getUserContext($this);
        $this->load->helper('form');
        $this->load->library('form_validation');
        $data['title'] = lang('hr_leaves_create_title');
        $data['form_action'] = 'hr/leaves/create/' . $id;
        $data['source'] = 'hr/employees';
        $data['employee'] = $id;
        
        $this->form_validation->set_rules('startdate', lang('hr_leaves_create_field_start'), 'required|xss_clean|strip_tags');
        $this->form_validation->set_rules('startdatetype', 'Start Date type', 'required|xss_clean|strip_tags');
        $this->form_validation->set_rules('enddate', lang('hr_leaves_create_field_end'), 'required|xss_clean|strip_tags');
        $this->form_validation->set_rules('enddatetype', 'End Date type', 'required|xss_clean|strip_tags');
        $this->form_validation->set_rules('duration', lang('hr_leaves_create_field_duration'), 'required|xss_clean|strip_tags');
        $this->form_validation->set_rules('type', lang('hr_leaves_create_field_type'), 'required|xss_clean|strip_tags');
        $this->form_validation->set_rules('cause', lang('hr_leaves_create_field_cause'), 'xss_clean|strip_tags');
        $this->form_validation->set_rules('status', lang('hr_leaves_create_field_status'), 'required|xss_clean|strip_tags');

        $data['credit'] = 0;
        if ($this->form_validation->run() === FALSE) {
            $this->load->model('types_model');
            $data['types'] = $this->types_model->getTypes();
            foreach ($data['types'] as $type) {
                if ($type['id'] == 0) {
                    $data['credit'] = $this->leaves_model->getLeavesTypeBalanceForEmployee($id, $type['name']);
                    break;
                }
            }
            $this->load->model('users_model');
            $data['name'] = $this->users_model->getName($id);
            $this->load->view('templates/header', $data);
            $this->load->view('menu/index', $data);
            $this->load->view('hr/createleave');
            $this->load->view('templates/footer');
        } else {
            $this->leaves_model->setLeaves($id);   //Return not used
            $this->session->set_flashdata('msg', lang('hr_leaves_create_flash_msg_success'));
            
            redirect('hr/employees');
        }
    }
    
   
    public function presence($source, $id, $month=0, $year=0) {
        if ($source == 'collaborators') { $this->auth->checkIfOperationIsAllowed('list_collaborators'); }
        if ($source == 'employees') { $this->auth->checkIfOperationIsAllowed('list_employees'); }
        $data = getUserContext($this);
        if ($source == 'collaborators') { $data['source'] = 'collaborators'; }
        if ($source == 'employees') { $data['source'] = 'employees'; }
        $this->lang->load('datatable', $this->language);
        $this->lang->load('calendar', $this->language);
        $data['title'] = lang('hr_presence_title');
        $data['help'] = $this->help->create_help_link('global_link_doc_page_presence_report');
        
        $data['user_id'] = $id;
        $this->load->model('leaves_model');
        $this->load->model('users_model');
        $this->load->model('dayoffs_model');
        $this->load->model('contracts_model');
        
        
        $employee = $this->users_model->getUsers($id);
        if (($this->user_id != $employee['manager']) && ($this->is_hr === FALSE)) {
            log_message('error', 'User #' . $this->user_id . ' illegally tried to access to hr/presence  #' . $id);
            $this->session->set_flashdata('msg', sprintf(lang('global_msg_error_forbidden'), 'hr/presence'));
            redirect('leaves');
        }
        $data['employee_name'] =  $employee['firstname'] . ' ' . $employee['lastname'];
        $contract = $this->contracts_model->getContracts($employee['contract']);
        if (!empty($contract)) {
            $data['contract_id'] = $contract['id'];
            $data['contract_name'] = $contract['name'];
        } else {
            $data['contract_id'] = '';
            $data['contract_name'] = '';
        }
        
       
        if ($month == 0) $month = date('m', strtotime('last month'));
        if ($year == 0) $year = date('Y', strtotime('last month'));
        $total_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $start = sprintf('%d-%02d-01', $year, $month);
        $lastDay = date("t", strtotime($start));    
        $end = sprintf('%d-%02d-%02d', $year, $month, $lastDay);
       
        $non_working_days = $this->dayoffs_model->lengthDaysOffBetweenDates($employee['contract'], $start, $end);
        $opened_days = $total_days - $non_working_days;
        $data['month'] = $month;
        $data['month_name'] = date('F', strtotime($start));
        $data['year'] = $year;
        $data['default_date'] = $start;
        $data['total_days'] = $total_days;
        $data['opened_days'] = $opened_days;
        $data['non_working_days'] = $non_working_days;
        
   
        $data['linear'] = $this->leaves_model->linear($id, $month, $year, FALSE, FALSE, TRUE, FALSE);
        $data['leave_duration'] = $this->leaves_model->monthlyLeavesDuration($data['linear']);
        $data['work_duration'] = $opened_days - $data['leave_duration'];
        $data['leaves_detail'] = $this->leaves_model->monthlyLeavesByType($data['linear']);
        
       
        $data['leaves'] = $this->leaves_model->getAcceptedLeavesBetweenDates($id, $start, $end);
        
     
        $data['employee_id'] = $id;
        $refDate = new DateTime($end);
        $data['refDate'] = $refDate->format(lang('global_date_format'));
        $data['summary'] = $this->leaves_model->getLeaveBalanceForEmployee($id, FALSE, $end);
        
        $this->load->view('templates/header', $data);
        $this->load->view('menu/index', $data);
        $this->load->view('hr/presence', $data);
        $this->load->view('templates/footer');
    }
        
 
    public function exportLeaves($id) {
        $this->load->library('excel');
        $this->load->model('leaves_model');
        $this->load->model('users_model');
        $data['id'] = $id;
        $this->load->view('hr/export_leaves', $data);
    }
    
  
    public function exportOvertime($id) {
        $this->load->library('excel');
        $this->load->model('overtime_model');
        $this->load->model('users_model');
        $data['id'] = $id;
        $this->load->view('hr/export_overtime', $data);
    }
    
   
    public function exportEmployees($id = 0, $children = TRUE) {
        $this->load->model('users_model');
        $this->load->library('excel');
        $data['id'] = $id;
        $data['children'] = filter_var($children, FILTER_VALIDATE_BOOLEAN);
        $this->load->view('hr/export_employees', $data);
    }
    
  
    public function exportPresence($source,$id, $month=0, $year=0) {
        if ($source == 'collaborators') { $this->auth->checkIfOperationIsAllowed('list_collaborators'); }
        if ($source == 'employees') { $this->auth->checkIfOperationIsAllowed('list_employees'); }
        setUserContext($this);
        $this->lang->load('calendar', $this->language);
        $this->load->model('leaves_model');
        $this->load->model('users_model');
        $this->load->model('dayoffs_model');
        $this->load->model('contracts_model');
        
        $employee = $this->users_model->getUsers($id);
        if (($this->user_id != $employee['manager']) && ($this->is_hr === FALSE)) {
            log_message('error', 'User #' . $this->user_id . ' illegally tried to access to hr/presence  #' . $id);
            $this->session->set_flashdata('msg', sprintf(lang('global_msg_error_forbidden'), 'hr/presence'));
            redirect('leaves');
        }
        
        $this->load->library('excel');       
        $data['employee'] = $employee;
        $data['month'] = $month;
        $data['year'] = $year;
        $data['id'] = $id;
        $this->load->view('hr/export_presence', $data);
    }
}