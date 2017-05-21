<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    // 添加问题api
    public function add()
    {
        // 检查用户是否登录
        if (!is_logged_in())
        {
            return err(['msg' => 'login required']);
        }

        // 检查是否存在question_id and content required
        if (!rq('question_id') || !rq('content'))
        {
            return err(['msg' => 'question_id and content required']);
        }

        // 检查问题是否存在
        $question = question_ins()->find(rq('question_id'));
        if (!$question)
        {
            return err(['msg' => 'question not exists']);
        }

        // 检查是否重复回答
        $answered = $this->where([
            'question_id' => rq('question_id'),
            'user_id' => session('user_id')
        ])->count();

        if ($answered)
        {
            return err(['msg' => 'duplicate answers']);
        }

        $this->content = rq('content');
        $this->question_id = rq('question_id');
        $this->user_id = session('user_id');

        // 保存数据
        return $this->save() ?
            suc(['id' => $this->id]) :
            err(['msg' => 'db insert failed']);
    }

    // 更新回答api
    public function change()
    {
        //检查用户是否登录
        if (!is_logged_in())
        {
            return err(['msg' => 'login required']);
        }

        //检查是否有id和content
        if (!rq('id') || !rq('content'))
        {
            return err(['msg' => 'id and content are required']);
        }

        //检查更新的问题是不是同一个id
        $answer = $this->find(rq('id'));
        if ($answer->user_id != session('user_id'))
        {
            return err(['msg' => 'permission denied']);
        }

        //保存数据
        $answer->content = rq('content');
        return $answer->save() ?
            suc() :
            err(['msg' => 'db update failed']);
    }

    public function read_by_user_id($user_id)
    {
        $user = user_ins()->find($user_id);
        if (!$user)
        {
            return err(['msg' => 'user not exists']);
        }

        $r = $this
            ->with('question')
            ->where('user_id', $user_id)
            ->get()
            ->keyBy('id');

        return suc($r->toArray());
    }

    //查看回答api
    public function read()
    {
        //检查问题id
        if (!rq('id') && !rq('question_id') && !rq('user_id'))
        {
            return err(['msg' => 'id or question_id is required']);
        }

        if (rq('user_id'))
        {
            $user_id = rq('user_id') === 'self' ?
                session('user_id') :
                rq('user_id');
            return $this->read_by_user_id($user_id);
        }

        //单个回答查看
        if (rq('id'))
        {
            $answer = $this
                ->with('user')
                ->with('users')
                ->find(rq('id'));
            if (!$answer)
            {
                return err(['msg' => 'answer not exists']);
            }

            $answer = $this->count_vote($answer);
            return suc(['data' => $answer]);
        }

        //在查看问题前，检查问题是否存在
        if (!question_ins()->find(rq('question_id')))
        {
            return err(['msg' => 'question not exists']);
        }

        //查看同一问题下所有回答
        $answer = $this
            ->where('question_id', rq('question_id'))
            ->get()
            ->keyBy('id');

        return suc(['data' => $answer]);
    }

    public function count_vote($answer)
    {
        $upvote_count = 0;
        $downvote_count = 0;
        foreach ($answer->users as $user)
        {
            if ($user->pivot->vote == 1)
            {
                $upvote_count++;
            } else
            {
                $downvote_count++;
            }
        }
        $answer->upvote_count = $upvote_count;
        $answer->downvote_count = $downvote_count;
        return $answer;
    }

    //删除回答api
    public function remove()
    {
        if (!is_logged_in())
        {
            return err(['msg' => 'login required']);
        }

        if (!rq('id'))
        {
            return err(['msg' => 'id is required']);
        }

        $answer = $this->find(rq('id'));
        if (!$answer)
        {
            return err(['msg' => 'answer not exists']);
        }

        if ($answer->user_id != session('user_id'))
        {
            return err(['msg' => 'permission denied']);
        }

        return $answer->delete() ?
            suc() :
            err(['msg' => 'db delete failed']);
    }

    //投票api
    public function vote()
    {
        if (!is_logged_in())
        {
            return err(['msg' => 'login required']);
        }

        if (!rq('id') || !rq('vote'))
        {
            return err(['msg' => 'id and vote are required']);
        }

        $answer = $this->find(rq('id'));
        if (!$answer)
        {
            return err(['msg' => 'answer not exists']);
        }

        //1赞成，2反对，3反对
        $vote = rq('vote');
        if ($vote != 1 && $vote != 2 && $vote != 3)
        {
            return ['status' => 0, 'msg' => 'invalid vote'];
        }

        //检查此用户是否相同问题下投过票，如果投过票删除之前的结果
        $voted=$answer
            ->users()
            ->newPivotStatement()
            ->where('user_id', session('user_id'))
            ->where('answer_id', rq('id'))
            ->delete();

        if ($vote == 3)
        {
            return ['status' => 1];
        }

        $answer
            ->users()
            ->attach(session('user_id'), ['vote' => (int)rq('vote')]);

        return suc();
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function users()
    {
        return $this
            ->belongsToMany('App\User')
            ->withPivot('vote')
            ->withTimestamps();
    }

    public function question()
    {
        return $this->belongsTo('App\Question');
    }
}
