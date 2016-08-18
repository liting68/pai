<?php
defined('BASEPATH') or exit ('No direct script access allowed');

class Course extends CI_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->library(array('session', 'pagination'));
        $this->load->helper(array('form', 'url', 'download'));
        $this->load->model(array('user_model', 'useractionlog_model', 'course_model', 'teacher_model', 'homework_model', 'homeworklist_model', 'survey_model', 'surveylist_model', 'ratings_model', 'student_model', 'department_model'));

        $this->_logininfo = $this->session->userdata('loginInfo');
        if (empty($this->_logininfo)) {
            redirect('login', 'index');
        } else {
            $loginInfo = $this->_logininfo;
            $roleInfo = $this->session->userdata('roleInfo');
            if ($loginInfo['role'] != 1) {
                $redirect_flag = true;
                foreach ($roleInfo as $key => $value) {
                    if (strpos(current_url(), $key)) {//包含则不用跳转
                        $redirect_flag = false;
                    }
                }
                if ($redirect_flag) {
                    redirect($_SERVER['HTTP_REFERER']);
                }
            }
            $this->useractionlog_model->create(array('user_id' => $this->_logininfo['id'], 'url' => uri_string()));
            $this->load->vars(array('loginInfo' => $this->_logininfo, 'roleInfo' => $roleInfo));
        }

    }

    private function escapeVal($val){
        return !empty($val)?$this->db->escape($val):'';
    }

    public function courselist()
    {
        $logininfo = $this->_logininfo;
        $page = $this->input->get('per_page', true);
        $page = $page * 1 < 1 ? 1 : $page;
        $page_size = 10;
        $parm['status'] = $this->input->get('status');
        $parm['keyword'] = $this->input->get('keyword');
        $parm['time_start'] = $this->input->get('time_start');
        $parm['time_end'] = $this->input->get('time_end');
        $pvalue=array_map(array($this,'escapeVal'),$parm);//防sql注入
        $this->load->database();
        //status 1报名中2进行中3结束4待发布5待开启报名9其他
        $sql = "select c.*,t.name as teacher,if( c.ispublic != 1,4,if( unix_timestamp(now()) > unix_timestamp(c.time_end),3,if( unix_timestamp(now()) > unix_timestamp(c.time_start) and unix_timestamp(now()) < unix_timestamp(c.time_end),2,if( isapply_open !=1 ,5,if(unix_timestamp(now()) > unix_timestamp(c.apply_start) and unix_timestamp(now()) < unix_timestamp(c.apply_end),1,9) ) ) ) ) as status from " . $this->db->dbprefix('course') . " c "
            . "left join " . $this->db->dbprefix('teacher') . " t on c.teacher_id=t.id "
            . "where c.company_code = " . $logininfo['company_code'] . " and c.isdel=2 ";
        if ($parm['status'] == 4) {//待发布
            $sql .= " and c.ispublic != 1";
        } elseif ($parm['status'] == 3) {//结束
            $sql .= " and c.ispublic = 1 and unix_timestamp(now()) > unix_timestamp(c.time_end) ";
        } elseif ($parm['status'] == 2) {//进行中
            $sql .= " and c.ispublic = 1 and unix_timestamp(now()) > unix_timestamp(c.time_start) and unix_timestamp(now()) < unix_timestamp(c.time_end) ";
        } elseif ($parm['status'] == 1) {//报名中
            $sql .= " and c.ispublic = 1 and isapply_open=1 and unix_timestamp(now()) > unix_timestamp(c.apply_start) and unix_timestamp(now()) < unix_timestamp(c.apply_end)";
        } elseif ($parm['status'] == 5) {//报名未开始
            $sql .= " and c.ispublic = 1 and (isapply_open!=1 or unix_timestamp(now()) < unix_timestamp(c.apply_start) ) and unix_timestamp(now()) < unix_timestamp(c.time_start) ";
        }
        if (!empty($parm['keyword'])) {
            $sql .= " and (c.title like '%" . $parm['keyword'] . "%' )";
        }
        if (!empty($parm['time_start'])) {
            $sql .= " and unix_timestamp(time_start) >= unix_timestamp(" . $pvalue['time_start']  . ") ";
        }
        if (!empty($parm['time_end'])) {
            $sql .= " and unix_timestamp(time_start) <= unix_timestamp(" . $pvalue['time_end'] . ") ";
        }
        $query = $this->db->query("select count(*) as num from ($sql) s ");
        $num = $query->row_array();
        $total_rows = $num['num'];
        $config['base_url'] = site_url('course/courselist') . '?keyword=' . $parm['keyword'] . '&time_start=' . $parm['time_start'] . '&time_end=' . $parm['time_end'] . '&status=' . $parm['status'];
        $config['per_page'] = $page_size;
        $config['total_rows'] = $total_rows;
        $this->pagination->initialize($config);

        $query = $this->db->query($sql . " order by c.id desc limit " . ($page - 1) * $page_size . "," . $page_size);
        $courses = $query->result_array();
        $this->load->view('header');
        $this->load->view('course/list', array('courses' => $courses, 'parm' => $parm, 'links' => $this->pagination->create_links()));
        $this->load->view('footer');

    }

    //创建课程
    public function coursecreate()
    {
        $logininfo = $this->_logininfo;
        $act = $this->input->post('act');
        $msg = '';
        $c = array();
        if (!empty($act)) {
            $logininfo = $this->_logininfo;
            $c = array('user_id' => $logininfo['id'],
                'company_code' => $logininfo['company_code'],
                'title' => $this->input->post('title'),
                'time_start' => $this->input->post('time_start'),
                'time_end' => $this->input->post('time_end'),
                'address' => $this->input->post('address'),
                'teacher_id' => $this->input->post('teacher_id'),
                'target' => $this->input->post('target'),
                'targetone' => $this->input->post('targetone'),
                'targettwo' => $this->input->post('targettwo'),
                'targetstudent' => $this->input->post('targetstudent'),
                'price' => $this->input->post('price'),
                'income' => $this->input->post('income'),
                'outline' => $this->input->post('outline'),
                'info' => $this->input->post('info'));
            if (empty($c['teacher_id'])) {
                unset($c['teacher_id']);
            }
            $config['upload_path'] = './uploads/course_img';
            $config['allowed_types'] = 'gif|jpg|png|jpeg';
            $config['file_name'] = $file_name = $logininfo['id'] . date("YmdHis");

            $this->load->library('upload', $config);
            if ($this->upload->do_upload('page_img')) {
                $img = $this->upload->data();
                $c['page_img'] = $file_name . $img['file_ext'];
                //缩略
                $config['image_library'] = 'gd2';
                $config['source_image'] = './uploads/course_img/' . $c['page_img'];
                $config['create_thumb'] = FALSE;
                $config['maintain_ratio'] = TRUE;
                $config['width'] = 320;
                $this->load->library('image_lib', $config);
                $this->image_lib->resize();
            }
            $c['ispublic'] = $this->input->post('public') == 1 ? 1 : 2;
            $id = $this->course_model->create($c);
            if ($c['ispublic'] == 1) {//如果发布则开启报名
                $apply = array('isapply_open' => 1, 'apply_start' => date("Y-m-d H:i:s", strtotime("-1months", strtotime($c['time_start']))), 'apply_end' => date("Y-m-d H:i:s", strtotime("-1days", strtotime($c['time_start']))), 'apply_num' => 0, 'apply_check' => 2);
                $this->course_model->update($apply, $id);
                $this->load->library(array('notifyclass'));
                $this->notifyclass->applyopen($id);
            }
            redirect(site_url('course/courseinfo/'.$id));
            return;
        }
        $teachers = $this->teacher_model->get_all(array('company_code' => $logininfo['company_code'], 'isdel' => 2));
        //培训对象数据
        $deparone = $this->department_model->get_all(array('company_code' => $logininfo['company_code'], 'level' => 0));
        if (!empty($deparone[0]['id'])) {
            $departwo = $this->department_model->get_all(array('parent_id' => $deparone[0]['id']));
        }
        if (!empty($departwo[0]['id'])) {
            $students = $this->student_model->get_all(array('department_id' => $departwo[0]['id'],'isdel'=>2));
        }
        $this->load->view('header');
        $this->load->view('course/edit', array('teachers' => $teachers, 'course' => $c, 'deparone' => $deparone, 'departwo' => $departwo, 'students' => $students, 'msg' => $msg));
        $this->load->view('footer');
    }

    //课程编辑
    public function courseedit($id)
    {
        $logininfo = $this->_logininfo;
        $act = $this->input->post('act');
        if (!empty($act)) {
            $logininfo = $this->_logininfo;
            $c = array('company_code' => $logininfo['company_code'],
                'title' => $this->input->post('title'),
                'time_start' => $this->input->post('time_start'),
                'time_end' => $this->input->post('time_end'),
                'address' => $this->input->post('address'),
                'teacher_id' => $this->input->post('teacher_id'),
                'target' => $this->input->post('target'),
                'targetone' => $this->input->post('targetone'),
                'targettwo' => $this->input->post('targettwo'),
                'targetstudent' => $this->input->post('targetstudent'),
                'price' => $this->input->post('price'),
                'income' => $this->input->post('income'),
                'outline' => $this->input->post('outline'),
                'info' => $this->input->post('info'));
            if (empty($c['teacher_id'])) {
                $c['teacher_id'] = NULL;
            }
            $config['max_size'] = 5*1024;
            $config['upload_path'] = './uploads/course_img';
            $config['allowed_types'] = 'gif|jpg|png|jpeg';
            $config['file_name'] = $file_name = $logininfo['id'] . date("YmdHis");

            $this->load->library('upload', $config);
            if ($this->upload->do_upload('page_img')) {
                $img = $this->upload->data();
                $c['page_img'] = $file_name . $img['file_ext'];
                //缩略
                $config['image_library'] = 'gd2';
                $config['source_image'] = './uploads/course_img/' . $c['page_img'];
                $config['create_thumb'] = FALSE;
                $config['maintain_ratio'] = TRUE;
                $config['width'] = 320;
                $this->load->library('image_lib', $config);
                $this->image_lib->resize();
            }
            $c['ispublic'] = $this->input->post('public') == 1 ? 1 : 2;
            $this->course_model->update($c, $id);
            $msg = '课程保存成功';
        }
        $teachers = $this->teacher_model->get_all(array('company_code' => $logininfo['company_code'], 'isdel' => 2));
        //培训对象数据
        $deparone = $this->department_model->get_all(array('company_code' => $logininfo['company_code'], 'level' => 0));
        if (!empty($deparone[0]['id'])) {
            $departwo = $this->department_model->get_all(array('parent_id' => $deparone[0]['id']));
        }
        if (!empty($departwo[0]['id'])) {
            $students = $this->student_model->get_all(array('department_id' => $departwo[0]['id'],'isdel'=>2));
        }
        $course = $this->course_model->get_row(array('id' => $id,'company_code' => $logininfo['company_code']));
        $this->load->view('header');
        $this->load->view('course/edit', array('teachers' => $teachers, 'course' => $course, 'msg' => $msg, 'deparone' => $deparone, 'departwo' => $departwo, 'students' => $students));
        $this->load->view('footer');
    }

    //课程详情
    public function courseinfo($id)
    {
        $course = $this->course_model->get_row(array('id' => $id,'company_code' => $this->_logininfo['company_code']));
        $teacher = $this->teacher_model->get_row(array('id' => $course['teacher_id']));
        $this->load->view('header');
        $this->load->view('course/info', array('course' => $course, 'teacher' => $teacher));
        $this->load->view('footer');
    }

    //报名设置
    public function applyset($id)
    {
        $act = $this->input->post('act');
        if (!empty($act)) {
            $oldcourse = $this->course_model->get_row(array('id' => $id,'company_code' => $this->_logininfo['company_code']));
            $c = array('isapply_open' => $this->input->post('isapply_open'),
                'apply_start' => $this->input->post('apply_start'),
                'apply_end' => $this->input->post('apply_end'),
                'apply_num' => $this->input->post('apply_num'),
                'apply_check_type' => $this->input->post('apply_check_type'),
                'apply_tip' => $this->input->post('apply_tip'));
            if ($this->input->post('apply_check') == 1) {
                $c['apply_check'] = 1;
            } else {
                $c['apply_check'] = 2;
            }
            $this->course_model->update($c, $id);
            if ($oldcourse['isapply_open'] != 1 && $c['isapply_open'] == 1) {
                $this->load->library(array('notifyclass'));
                $this->notifyclass->applyopen($id);
            }
        }
        $course = $this->course_model->get_row(array('id' => $id));
        $this->load->view('header');
        $this->load->view('course/apply_set', array('course' => $course));
        $this->load->view('footer');
    }

    //报名名单
    public function applylist($id)
    {
        $course = $this->course_model->get_row(array('id' => $id,'company_code' => $this->_logininfo['company_code']));
        $pargram['applystatus'] = $this->input->get('applystatus', true);
        $page = $this->input->get('per_page', true);
        $page = $page * 1 < 1 ? 1 : $page;
        $page_size = 10;
        $this->load->database();
        //报名人数
        $this->db->where('course_id', $id)->from('course_apply_list');
        $total = $this->db->count_all_results();
        //拒绝人数
        $this->db->where(array('course_id' => $id, 'status' => 2))->from('course_apply_list');
        $refusetotal = $this->db->count_all_results();

        $sql = "select s.*,d.name as department,a.id as apply_id,a.status as apply_status,a.note "
            . "from " . $this->db->dbprefix('course_apply_list') . " a left join " . $this->db->dbprefix('student') . " s on a.student_id=s.id "
            . "left join " . $this->db->dbprefix('department') . " d on s.department_id = d.id "
            . "where a.course_id=$id ";
        $query = $this->db->query("select count(*) as num from ($sql) s ");
        $num = $query->row_array();
        $total_rows = $num['num'];
        $config['base_url'] = base_url('course/applylist/' . $id);
        $config['per_page'] = $page_size;
        $config['total_rows'] = $total_rows;
        $this->pagination->initialize($config);
        if (!empty($pargram['applystatus'])) {
            $sql .= " and a.status = '{$pargram['applystatus']}' ";
        }
        $query = $this->db->query($sql . " order by a.id desc limit " . ($page - 1) * $page_size . "," . $page_size);
        $applys = $query->result_array();
        $this->load->view('header');
        $this->load->view('course/apply_list', array('course' => $course, 'total' => $total, 'refusetotal' => $refusetotal, 'applys' => $applys, 'pargram' => $pargram, 'links' => $this->pagination->create_links()));
        $this->load->view('footer');
    }

    //报名审核操作
    public function applycheck($id)
    {
        $logininfo = $this->_logininfo;
        $status = $this->input->get('status', true);
        if (!empty($status)) {
            $this->load->database();
            $sql = "select c.company_code,a.course_id,a.student_id from " . $this->db->dbprefix('course_apply_list') . " a "
                . "left join " . $this->db->dbprefix('course') . " c on a.course_id=c.id where a.id = $id ";
            $query = $this->db->query($sql);
            $obj = $query->row_array();
            if ($logininfo['company_code'] == $obj['company_code']) {
                $this->db->where('id', $id);
                $this->db->update('course_apply_list', array('status' => $status));
                //发送报名成功通知
                if($status==1){
                    $this->load->library(array('notifyclass'));
                    $this->notifyclass->applysuccess($obj['course_id'], $obj['student_id']);
                }
            }
        }
        redirect($_SERVER['HTTP_REFERER']);
    }

    //签到设置
    public function signinset($id)
    {
        $act = $this->input->post('act');
        if (!empty($act)) {
            $c = array('issignin_open' => $this->input->post('issignin_open'),
                'signin_start' => $this->input->post('signin_start'),
                'signin_end' => $this->input->post('signin_end'),
                'signout_start' => $this->input->post('signout_start'),
                'signout_end' => $this->input->post('signout_end'));
            if (empty($c['signout_start'])) {
                $c['signout_start'] = NULL;
            }
            if (empty($c['signout_end'])) {
                $c['signout_end'] = NULL;
            }
            $this->course_model->update($c, $id);
        }
        $course = $this->course_model->get_row(array('id' => $id,'company_code' => $this->_logininfo['company_code']));
        if (empty($course['signin_qrcode'])) {
            $course['signin_qrcode'] = $course['id'] . rand(1000, 9999);
            $this->load->library('ciqrcode');
            $params['data'] = $this->config->item('web_url') . 'course/signin/' . $course['id'] . '/' . $course['signin_qrcode'];
            $params['level'] = 'H';
            $params['size'] = 1025;
            $params['savename'] = './uploads/course_qrcode/' . $course['signin_qrcode'] . '.png';
            $this->ciqrcode->generate($params);
            $course['signout_qrcode'] = $course['id'] . rand(1000, 9999);
            $params['data'] = $this->config->item('web_url') . 'course/signout/' . $course['id'] . '/' . $course['signout_qrcode'];
            $params['level'] = 'H';
            $params['size'] = 1025;
            $params['savename'] = './uploads/course_qrcode/' . $course['signout_qrcode'] . '.png';
            $this->ciqrcode->generate($params);
            $this->course_model->update($course, $course['id']);
        }
        $this->load->view('header');
        $this->load->view('course/signin_set', array('course' => $course));
        $this->load->view('footer');
    }

    //下载签到二维码
    public function downloadqrcode($courseid)
    {
        $type = $this->input->get('type');
        $course = $this->course_model->get_row(array('id' => $courseid,'company_code' => $this->_logininfo['company_code']));
        if ($type == 'signin') {
            force_download('./uploads/course_qrcode/' . $course['signin_qrcode'] . '.png', NULL);
        } elseif ($type == 'signout') {
            force_download('./uploads/course_qrcode/' . $course['signout_qrcode'] . '.png', NULL);
        }

    }

    //签到名单
    public function signinlist($id)
    {
        $course = $this->course_model->get_row(array('id' => $id,'company_code' => $this->_logininfo['company_code']));
        $page = $this->input->get('per_page', true);
        $page = $page * 1 < 1 ? 1 : $page;
        $page_size = 10;
        $this->load->database();
        //签到人数
        $this->db->where("course_id = $id and signin_time <> '' and signin_time is not null")->from('course_signin_list');
        $signin_count = $this->db->count_all_results();
        //签退人数
        $this->db->where("course_id = $id and signout_time <> '' and signout_time is not null")->from('course_signin_list');
        $signout_count = $this->db->count_all_results();

        $sql = "select s.*,d.name as department,siginlist.id as siginlist_id,siginlist.signin_time,siginlist.signout_time "
            . "from " . $this->db->dbprefix('course_signin_list') . " siginlist left join " . $this->db->dbprefix('student') . " s on siginlist.student_id=s.id "
            . "left join " . $this->db->dbprefix('department') . " d on s.department_id = d.id "
            . "where siginlist.course_id=$id ";
        $query = $this->db->query("select count(*) as num from ($sql) s ");
        $num = $query->row_array();
        $total_rows = $num['num'];
        $config['base_url'] = base_url('course/signinlist/' . $id);
        $config['per_page'] = $page_size;
        $config['total_rows'] = $total_rows;
        $this->pagination->initialize($config);
        $query = $this->db->query($sql . " order by siginlist.id desc limit " . ($page - 1) * $page_size . "," . $page_size);
        $siginlist = $query->result_array();
        $this->load->view('header');
        $this->load->view('course/signin_list', array('course' => $course, 'signin_count' => $signin_count, 'signout_count' => $signout_count, 'siginlist' => $siginlist, 'links' => $this->pagination->create_links()));
        $this->load->view('footer');
    }

    //课前作业编辑
    public function homeworkedit($id)
    {
        $act = $this->input->post('act');
        if (!empty($act)) {
            $this->load->database();
            $this->db->where('course_id', $id);
            $this->db->delete('course_homework');
            $homeworks = $this->input->post('homeworks');
            foreach ($homeworks as $k => $h) {
                if (!empty($h)) {
                    $o = array('course_id' => $id, 'num' => $k + 1, 'title' => $h);
                    $this->homework_model->create($o);
                    $this->homeworklist_model->del(array('course_id' => $id));
                }
            }
        }
        $anstotal = $this->homeworklist_model->count(array('course_id' => $id));
        $homeworks = $this->homework_model->get_all(array('course_id' => $id));
        $course = $this->course_model->get_row(array('id' => $id));
        $this->load->view('header');
        $this->load->view('course/homework_edit', array('course' => $course, 'homeworks' => $homeworks, 'anstotal' => $anstotal));
        $this->load->view('footer');
    }

    //课前作业提交名单
    public function homeworklist($courseid)
    {
        $page = $this->input->get('per_page', true);
        $page = $page * 1 < 1 ? 1 : $page;
        $page_size = 10;
        $this->load->database();
        $listsql = "select * from " . $this->db->dbprefix('course_homework_list') . " h where course_id=$courseid group by student_id order by created desc ";
        $sql = "select h.*,s.name,s.job_code,s.job_name,d.name as department,s.mobile from ($listsql) h left join " . $this->db->dbprefix('student') . " s on h.student_id = s.id "
            . "left join " . $this->db->dbprefix('department') . " d on s.department_id = d.id ";
        $query = $this->db->query("select count(*) as num from ($sql) s ");
        $num = $query->row_array();
        $total_rows = $num['num'];
        $config['base_url'] = base_url('course/homeworklist/' . $courseid);
        $config['per_page'] = $page_size;
        $config['total_rows'] = $total_rows;
        $this->pagination->initialize($config);
        $query = $this->db->query($sql . " order by h.created desc limit " . ($page - 1) * $page_size . "," . $page_size);
        $homeworklist = $query->result_array();
        $course = $this->course_model->get_row(array('id' => $courseid));
        $this->load->view('header');
        $this->load->view('course/homework_list', array('course' => $course, 'homeworklist' => $homeworklist, 'total' => $total_rows, 'links' => $this->pagination->create_links()));
        $this->load->view('footer');
    }

    //查看作业详情
    public function homeworkdetail($courseid, $studentid)
    {
        $this->load->database();
        $student = $this->student_model->get_row(array('id' => $studentid,'company_code'=>$this->_logininfo['company_code']));
        $sql = "select * from " . $this->db->dbprefix('course_homework_list') . " hwlist "
            . " where hwlist.course_id=$courseid and hwlist.student_id=$studentid order by hwlist.id ";
        $query = $this->db->query($sql);
        $homework = $query->result_array();
        $depart = $this->department_model->get_row(array('id', $student['department_id']));
        $student['department'] = $depart['name'];
        $this->load->view('course/homework_detail', array('homework' => $homework, 'student' => $student));

    }

    //课前作业编辑
    public function surveyedit($id)
    {
        $act = $this->input->post('act');
        if (!empty($act)) {
            $this->load->database();
            $this->db->where('course_id', $id);
            $this->db->delete('course_survey');
            $surveys = $this->input->post('surveys');
            foreach ($surveys as $k => $h) {
                if (!empty($h)) {
                    $o = array('course_id' => $id, 'num' => $k + 1, 'title' => $h);
                    $this->survey_model->create($o);
                    $this->surveylist_model->del(array('course_id' => $id));
                }
            }
        }
        $anstotal = $this->surveylist_model->count(array('course_id' => $id));
        $surveys = $this->survey_model->get_all(array('course_id' => $id));
        $course = $this->course_model->get_row(array('id' => $id));
        $this->load->view('header');
        $this->load->view('course/survey_edit', array('course' => $course, 'surveys' => $surveys, 'anstotal' => $anstotal));
        $this->load->view('footer');
    }

    //课前作业提交名单
    public function surveylist($courseid)
    {
        $page = $this->input->get('per_page', true);
        $page = $page * 1 < 1 ? 1 : $page;
        $page_size = 10;
        $this->load->database();
        $listsql = "select * from " . $this->db->dbprefix('course_survey_list') . " h where course_id=$courseid group by student_id order by created desc ";
        $sql = "select h.*,s.name,s.job_code,s.job_name,d.name as department,s.mobile from ($listsql) h left join " . $this->db->dbprefix('student') . " s on h.student_id = s.id "
            . "left join " . $this->db->dbprefix('department') . " d on s.department_id = d.id ";
        $query = $this->db->query("select count(*) as num from ($sql) s ");
        $num = $query->row_array();
        $total_rows = $num['num'];
        $config['base_url'] = base_url('course/surveylist/' . $courseid);
        $config['per_page'] = $page_size;
        $config['total_rows'] = $total_rows;
        $this->pagination->initialize($config);
        $query = $this->db->query($sql . " order by h.created desc limit " . ($page - 1) * $page_size . "," . $page_size);
        $surveylist = $query->result_array();
        $course = $this->course_model->get_row(array('id' => $courseid));
        $this->load->view('header');
        $this->load->view('course/survey_list', array('course' => $course, 'surveylist' => $surveylist, 'total' => $total_rows, 'links' => $this->pagination->create_links()));
        $this->load->view('footer');
    }

    //查看作业详情
    public function surveydetail($courseid, $studentid)
    {
        $this->load->database();
        $student = $this->student_model->get_row(array('id' => $studentid));
        $sql = "select * from " . $this->db->dbprefix('course_survey_list') . " hwlist "
            . " where hwlist.course_id=$courseid and hwlist.student_id=$studentid order by hwlist.id ";
        $query = $this->db->query($sql);
        $survey = $query->result_array();
        $depart = $this->department_model->get_row(array('id', $student['department_id']));
        $student['department'] = $depart['name'];
        $this->load->view('course/survey_detail', array('survey' => $survey, 'student' => $student));

    }

    //课前反馈编辑
    public function ratingsedit($id)
    {
        $act = $this->input->post('act');
        if (!empty($act)) {
            $this->load->database();
            $this->db->where(array('course_id' => $id));
            $this->db->delete("course_ratings");
            $this->db->where(array('course_id' => $id));
            $this->db->delete("course_ratings_list");
            $ratingses = $this->input->post('ratingses');
            $type = $this->input->post('type');
            foreach ($ratingses as $k => $h) {
                if (!empty($h)) {
                    $o = array('course_id' => $id, 'num' => $k + 1, 'type' => $type[$k], 'title' => $h);
                    $this->ratings_model->create($o);
                }
            }
        }
        $ratingses = $this->ratings_model->get_all(array('course_id' => $id));
        $course = $this->course_model->get_row(array('id' => $id));
        $this->load->view('header');
        $this->load->view('course/ratings_edit', array('course' => $course, 'ratingses' => $ratingses));
        $this->load->view('footer');
    }

    //课前反馈提交名单
    public function ratingslist($courseid)
    {
        $page = $this->input->get('per_page', true);
        $page = $page * 1 < 1 ? 1 : $page;
        $page_size = 10;
        $this->load->database();
        $listsql = "select h.* from " . $this->db->dbprefix('course_ratings_list') . " h "
            . "left join " . $this->db->dbprefix('course_ratings') . " rats on h.ratings_id=rats.id where h.course_id=$courseid and rats.num=1 ";
        $sql = "select h.*,s.name,s.job_code,s.job_name,d.name as department,s.mobile from ($listsql) h left join " . $this->db->dbprefix('student') . " s on h.student_id = s.id "
            . "left join " . $this->db->dbprefix('department') . " d on s.department_id = d.id ";
        $query = $this->db->query("SELECT avg(cast(star as decimal(5, 2))) as avgstar FROM ($sql) s ");
        $avg = $query->row_array();
        $avgstar = $avg['avgstar'];
        $query = $this->db->query("select count(*) as num from ($sql) s ");
        $num = $query->row_array();
        $total_rows = $num['num'];
        $config['base_url'] = base_url('course/ratingslist/' . $courseid);
        $config['per_page'] = $page_size;
        $config['total_rows'] = $total_rows;
        $this->pagination->initialize($config);
        $query = $this->db->query($sql . " order by h.created desc limit " . ($page - 1) * $page_size . "," . $page_size);
        $ratingslist = $query->result_array();
        $course = $this->course_model->get_row(array('id' => $courseid));
        $this->load->view('header');
        $this->load->view('course/ratings_list', array('course' => $course, 'ratingslist' => $ratingslist, 'total' => $total_rows, 'avgstar' => $avgstar, 'links' => $this->pagination->create_links()));
        $this->load->view('footer');
    }

    //查看评价详情
    public function ratingsdetail($courseid, $studentid)
    {
        $this->load->database();
        $student = $this->student_model->get_row(array('id' => $studentid));
        $sql = "select hw.type,hw.num,hw.title,hw.type,hwlist.star,hwlist.content,hwlist.created from " . $this->db->dbprefix('course_ratings_list') . " hwlist "
            . "left join " . $this->db->dbprefix('course_ratings') . " hw on hwlist.ratings_id=hw.id where hwlist.course_id=$courseid and hwlist.student_id=$studentid order by hw.num ";
        $query = $this->db->query($sql);
        $ratings = $query->result_array();
        $depart = $this->department_model->get_row(array('id'=>$student['department_id']));
        $student['department'] = $depart['name'];
        $this->load->view('course/ratings_detail', array('ratings' => $ratings, 'student' => $student));

    }

    //通知设置
    public function notifyset($id)
    {
        $act = $this->input->post('act');
        if (!empty($act)) {
            $c = array('isnotice_open' => $this->input->post('isnotice_open'));
            $c['notice_type_msg'] = ($this->input->post('notice_type_msg') == 1) ? 1 : 2;
            $c['notice_type_email'] = ($this->input->post('notice_type_email') == 1) ? 1 : 2;
            $c['notice_type_wx'] = ($this->input->post('notice_type_wx') == 1) ? 1 : 2;
            $c['notice_trigger_one'] = ($this->input->post('notice_trigger_one') == 1) ? 1 : 2;
            $c['notice_trigger_two'] = ($this->input->post('notice_trigger_two') == 1) ? 1 : 2;
            $c['notice_trigger_three'] = ($this->input->post('notice_trigger_three') == 1) ? 1 : 2;
            $this->course_model->update($c, $id);
        }
        $course = $this->course_model->get_row(array('id' => $id));
        $this->load->view('header');
        $this->load->view('course/notify_set', array('course' => $course));
        $this->load->view('footer');
    }

    //通知自定义
    public function notifycustomize($id)
    {
        $course = $this->course_model->get_row(array('id' => $id));
        $this->load->view('header');
        $this->load->view('course/notify_customize', array('course' => $course));
        $this->load->view('footer');
    }

    //课程删除
    public function coursedel($id)
    {
        if (!empty($id)) {
            $c = $this->course_model->get_row(array('id' => $id));
            if ($c['company_code'] == $this->_logininfo['company_code']) {
                $this->course_model->update(array('isdel' => 1), $id);
            }
        }
        redirect(site_url('course/courselist'));
    }

    //课程发布
    public function coursepublic($id){
        if (!empty($id)) {
            $c = $this->course_model->get_row(array('id' => $id));
            if ($c['company_code'] == $this->_logininfo['company_code']) {
                $this->course_model->update(array('ispublic' => 1), $id);
            }
        }
        redirect($_SERVER['HTTP_REFERER']);
    }


}
