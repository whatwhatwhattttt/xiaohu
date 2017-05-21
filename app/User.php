<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Request;
use Hash;

class User extends Model
{
    // api注册
    public function signup()
    {
        // 检查用户名和密码是否为空
        $has_username_and_password = $this->has_username_and_password();
        if (!$has_username_and_password)
        {
            return [
                'status' => 0,
                'msg' => '用户名和密码皆不可为空'
            ];
        }

        $username = $has_username_and_password[0];
        $password = $has_username_and_password[1];

        // 检查用户名是否存在
        $user_exists = $this->where('username', $username)->exists();

        if ($user_exists)
        {
            return err(['msg' => '用户名已存在']);
        }

        // 加密密码
        $hashed_password = Hash::make($password);

        // 存入数据库
        $user = $this;
        $user->password = $hashed_password;
        $user->username = $username;
        if ($user->save())
        {
            return suc(['id' => $user->id]);
        } else
        {
            return err(['msg' => 'db insert failed']);
        }
    }

    // api登录
    public function login()
    {

        // 检查用户名和密码是否存在
        $has_username_and_password = $this->has_username_and_password();
        if (!$has_username_and_password)
        {
            return err(['msg' => '用户名和密码皆不可为空']);
        }
        $username = $has_username_and_password[0];
        $password = $has_username_and_password[1];

        // 检查用户是否存在
        $user = $this->where('username', $username)->first();

        if (!$user)
        {
            return err(['msg' => '用户不存在']);
        }

        // 检查密码是否正确
        $hashed_password = $user->password;
        if (!Hash::check($password, $hashed_password))
        {
            return err(['msg' => '密码有误']);
        }

        // 将用户信息写入session
        session()->put('username', $user->username);
        session()->put('user_id', $user->id);

        // dd(session()->all());

        return suc(['id' => $user->id]);
    }

    //获取用户信息api
    public function read()
    {
        if (!rq('id'))
        {
            return err(['msg' => 'required id']);
        }

        if (rq('id') === 'self')
        {
            if (!is_logged_in())
            {
                return err(['msg' => 'login is required']);
            }
            $id = session('user_id');
        }
        else{
            $id=rq('id');
        }
/*
        $id = rq('id') === 'self' ?
            session('user_id') : rq('id');
        */

        $get = ['id', 'username', 'avatar_url', 'intro'];

        //$this->get($get);
        $user = $this->find($id, $get);
        $data = $user->toArray();
        $answer_count = answer_ins()->where('user_id', $id)->count();
        $question_count = question_ins()->where('user_id', $id)->count();
        $data['answer_count'] = $answer_count;
        $data['question_count'] = $question_count;

        return suc($data);
    }

    //获取用户名和密码
    public function has_username_and_password()
    {
        $username = rq('username');
        $password = rq('password');

        // 检查用户名和密码是否为空
        if ($username && $password)
        {
            return [
                $username,
                $password
            ];
        }

        return false;
    }

    // 登出api
    public function logout()
    {
        //删除username
        session()->forget('username', null);
        //删除user_id
        session()->forget('user_id', null);
        return suc();
    }

    // 检测用户是否登录
    public function is_logged_in()
    {
        //如果session中存在user_id就返回user_id否则返回false
        return is_logged_in();
    }

    //修改密码api
    public function change_password()
    {
        //return Hash::make('123');
        if (!$this->is_logged_in())
            return err(['msg' => 'login required']);

        if (!rq('old_password') || !rq('new_password'))
            return err(['msg' => 'old_password or new_password are required']);

        $user = $this->find(session('user_id'));

        if (!Hash::check('123', $user->password))
        {
            return err(['msg' => 'invalid old_password']);
        }

        $user->password = Hash::make(rq('new_password'));
        return $user->save() ?
            suc() :
            err(['msg' => 'db update failed']);

    }

    //找回密码api
    public function reset_password()
    {
        if ($this->is_root())
        {
            return err(['msg' => 'max frequency reached']);
        }

        if (!rq('phone'))
        {
            return err(['msg' => 'phone is required']);
        }

        $user = $this->where('phone', rq('phone'))->first();

        if (!$user)
        {
            return err(['msg' => 'invalid phone number']);
        }

        //生成验证码
        $captcha = $this->generate_captcha();

        $user->phone_captcha = $captcha;
        if ($user->save())
        {

            //如果生成验证码成功，发送验证短信
            $this->send_sms();

            //为下一次机器人调用做准备
            $this->update_robot_time();
            return suc();
        }
        return err(['msg' => 'db update failed']);
    }

    //验证找回密码api
    public function validate_reset_password()
    {
        if ($this->is_root(2))
        {
            return err(['msg' => 'max frequency reached']);
        }

        if (!rq('phone') || !rq('phone_captcha') || !rq('new_password'))
        {
            return err(['msg' => 'phone,new_password and phone captcha are required']);
        }

        $user = $this->where([
            'phone' => rq('phone'),
            'phone_captcha' => rq('phone_captcha')
        ])->first();

        if (!$user)
        {
            return err(['msg' => 'invalid phone or invalid phone_captcha']);
        }

        //加密新密码
        $user->password = Hash::make(rq('new_password'));
        $this->update_robot_time();
        return $user->save() ?
            suc() :
            err(['msg' => 'db update failed']);
    }

    //检查机器人
    public function is_root($time = 10)
    {
        //如果session中没有last_sms_time说明借口从未被调用
        if (!session('last_sms_time'))
        {
            return false;
        }

        $current_time = time();
        $last_active_time = session('last_sms_time');

        $elapsed = $current_time - $last_active_time;
        return !($elapsed > $time);
    }

    //更新机器人行为时间
    public function update_robot_time()
    {
        session()->set('last_sms_time', time());
    }

    //发送验证信息
    public function send_sms()
    {
        return true;
    }

    //生成验证码
    public function generate_captcha()
    {
        return rand(1000, 9999);
    }

    public function answers()
    {
        return $this
            ->belongsToMany('App\Answer')
            ->withPivot('vote')
            ->withTimestamps();
    }

    public function questions()
    {
        return $this
            ->belongsToMany('App\Question')
            ->withPivot('vote')
            ->withTimestamps();
    }

    public function exist()
    {
        return suc(['count' => $this->where(rq())->count()]);
    }
}
