<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/4/25
 * Time: 20:48
 */

namespace Home\Controller;


use Think\Controller;

class AUController extends Controller{
    public function index(){
        layout('au_layout');
        $this->display();
    }
    public function department(){
        layout('au_layout');
        $this->display();
    }
    public function regulation(){
        layout('au_layout');
        $this->display();
    }
    public function event(){
        layout('au_layout');
        $this->data=M('event')->select();
        $this->display();
    }
    public function weizai(){
        layout('au_layout');
        $this->display();
    }

}