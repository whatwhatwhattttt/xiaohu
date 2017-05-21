<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    // 创建问题
    public function add()
    {
        // 检查用户是否登录
        if (!is_logged_in())
        {
            return err(['msg' => 'login required']);
        }

        // 检查是否存在标题
        if (!rq('title'))
        {
            return err(['msg' => 'required title']);
        }

        // 保存
        $this->title = rq('title');
        $this->user_id = session('user_id');
        if (rq('desc'))
            $this->desc = rq('desc');

        return $this->save() ?
            suc(['id' => $this->id]) :
            err(['msg' => 'db insert failed']);
    }

    // 更新问题api
    public function change()
    {

        // 检查用户是否登录
        if (!is_logged_in())
        {
            return err(['msg' => 'login required']);
        }

        // 检查传参中是否有id
        if (!rq('id'))
        {
            return err(['msg' => 'id is required']);
        }

        // 获取制定id的model
        $question = $this->find(rq('id'));
        if ($question->user_id != session('user_id'))
        {
            return err(['msg' => 'permission denied']);
        }

        // 判断问题是否存在
        if (!question)
        {
            return err(['msg' => 'question not exists']);
        }

        if ($question->user_id != session('user_id'))
        {
            return err(['msg' => 'permission denied']);
        }

        if (rq('title'))
        {
            $question->title = rq('title');
        }

        if (rq('desc'))
        {
            $question->title = rq('desc');
        }

        // 保存数据
        return $question->save() ?
            suc(['status' => 1]) :
            err(['msg' => 'db update failed']);
    }

    public function read_by_user_id($user_id)
    {
        $user = user_ins()->find($user_id);
        if (!$user)
        {
            return err(['msg' => 'user not exists']);
        }

        $r = $this->where('user_id', $user_id)
            ->get()
            ->keyBy('id');

        return suc($r->toArray());
    }

    // 查看问题api
    public function read()
    {
        // 请求参数是否有id,如果有id直接返回id所有行
        if (rq('id'))
        {
            $r=$this
                ->with('answers_with_user_info')
                ->find(rq('id'));
            return suc(['data' => $r]);
        }

        if (rq('user_id'))
        {
            $user_id = rq('user_id') == 'self' ?
                session('user_id') :
                rq('user_id');
            return $this->read_by_user_id($user_id);
        }

        // limit条件
        list($limit, $skip) = paginate(rq('page'), rq('limit'));


        // 构建query并返回collection数据
        $r = $this->orderBy('created_at')
            ->limit($limit)
            ->skip($skip)
            ->get([
                'id',
                'title',
                'desc',
                'user_id',
                'created_at',
                'updated_at'
            ])
            ->keyBy('id');

        return suc(['data' => $r]);
    }


    // 删除问题api
    public function remove()
    {
        //检查用户是否登录
        if (!is_logged_in())
        {
            return err(['msg' => 'login required']);
        }

        //检查传参中是否有id
        if (!rq('id'))
        {
            return err(['msg' => 'id is required']);
        }

        //获取传参id所对应的model
        $question = $this->find(rq('id'));
        if (!$question)
        {
            return err(['question not exists']);
        }

        //检查当前用户是否为问题所有者
        if (session('user_id') != $question->user_id)
        {
            return err(['permission denied']);
        }

        return $question->delete() ?
            suc(['status' => 1]) :
            err(['status' => 0, 'msg' => 'db delete failed']);
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function answers()
    {
        return $this->hasMany('App\Answer');
    }

    public function answers_with_user_info()
    {
        return $this
            ->answers()
            ->with('user')
            ->with('users');
    }

}
