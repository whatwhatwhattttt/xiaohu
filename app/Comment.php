<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    //添加评论api
    public function add()
    {
        if (!is_logged_in()) {
            return err(['msg' => 'login required']);
        }

        if (!rq('content')) {
            return err(['msg' => 'empty content']);
        }

        if ((!rq('content') && !rq('answer_id')) || (rq('question_id') && rq('answer_id'))) {
            return err(['msg' => 'question_id or answer_id is required']);
        }

        //检查是否存在question_id或answer_id
        if (rq('question_id')) {
            //评论问题
            $question = question_ins()->find(rq('question_id'));
            //检查问题是否存在
            if (!$question) {
                return err(['msg' => 'question not exists']);
            }
            $this->question_id = rq('question_id');
        } else {
            //评论答案
            $answer = answer_ins()->find(rq('answer_id'));
            //检查答案是否存在
            if (!$answer) {
                return err(['msg' => 'answer not exists']);
            }
            $this->answer_id = rq('answer_id');
        }

        //检查是否回复评论
        if (rq('reply_to')) {
            $target = $this->find(rq('reply_to'));
            //检查是否回复自己的评论
            if (!$target) {
                return err(['msg' => 'target comment not exists']);
            }
            if ($target->user_id == session('user_id')) {
                return err(['cannot reply to yourself']);
            }
            $this->reply_to = rq('reply_to');
        }

        $this->content = rq('content');
        $this->user_id = session('user_id');
        return $this->save() ?
            suc(['id' => $this->id]) :
            err(['msg' => 'db insert failed']);
    }

    //查看评论api
    public function read()
    {
        if (!rq('question-id') && !rq('answer_id')) {
            return err(['question_id or answer_id is required']);
        }

        if (rq('question_id')) {
            $question = question_ins()->find(rq('question_id'));
            if (!$question) {
                return err(['question not exists']);
                $data = $this->where('question_id', rq('question_id'))->get();
            }
        } else {
            $answer = answer_ins()->find(rq('answer_id'));
            if (!$answer) {
                return err(['answer not exists']);
                $data = $this->where('answer_id', rq('answer_id'))->get();
            }
        }

        $data=$data->get()->keyby('id');
        return suc(['data' => $data]);
    }

    //删除评论api
    public function remove()
    {
        //检查是否登录
        if (!is_logged_in()) {
            return err(['msg' => 'login required']);
        }

        //检查是否携带id
        if (!rq('id')) {
            return err(['msg' => 'id is required']);
        }

        $comment = $this->find(rq('id'));
        if (!$comment) {
            return err(['msg' => 'comment not exists']);
        }

        if ($comment->user_id != session('user_id')) {
            return err(['msg' => 'permission denied']);
        }

        //检查该评论绑定的评并删除
        $this->where('reply_to', rq('id'))->delete();

        //删除该评论
        return $comment->delete() ?
            suc() :
            err(['db insert failed']);
    }
}
