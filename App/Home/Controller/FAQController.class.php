<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/4/25
 * Time: 14:16
 */

namespace Home\Controller;


use Think\Controller;

class FAQController extends Controller{
    public function index(){
        layout('faq_layout');
        $this->display();
    }
    public function faq_question(){
        layout('faq_layout');
        $this->display();
    }
    public function faq_financial(){
        layout('faq_layout');
        $this->display();
    }
    public function faq_secretary(){
        layout('faq_layout');
        $this->display();
    }
    public function faq_media(){
        layout('faq_layout');
        $this->display();
    }
    public function faq_art(){
        layout('faq_layout');
        $this->display();
    }
    public function faq_supervise(){
        layout('faq_layout');
        $this->display();
    }
    public function faq_guide(){
        layout('faq_layout');
        $this->display();
    }
    public function faq_hr(){
        layout('faq_layout');
        $this->display();
    }

}