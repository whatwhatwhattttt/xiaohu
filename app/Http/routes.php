<?php

/*
 * |--------------------------------------------------------------------------
 * | Application Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register all of the routes for an application.
 * | It's a breeze. Simply tell Laravel the URIs it should respond to
 * | and give it the controller to call when that URI is requested.
 * |
 */

function paginate($page = 1, $limit = 15)
{
    $limit = $limit ?: 15;
    $skip = ($page ? $page - 1 : 0) * $limit;
    return [$limit, $skip];
}

function err($msg_to_merge = null)
{
    return ['status' => 0,'msg'=>$msg_to_merge['msg']];
    /*if($msg_to_merge)
    {
        $msg=array_merge($msg,$msg_to_merge);
    }
    return $msg;*/
}

function suc($data_to_add = [])
{
    $data=['status' => 1,'data'=>[]];
    if($data_to_add)
    {
        $data['data']=$data_to_add;
    }
    return $data;
}

function is_logged_in()
{
    //如果session中存在user_id就返回user_id否则返回false
    return session('user_id') ?: false;
}

function rq($key = null, $default = null)
{
    if (!$key) {
        return Request::all();
    }
    return Request::get($key, $default);
}

function user_ins()
{
    return new App\User();
}

function question_ins()
{
    return new App\Question();
}

function answer_ins()
{
    return new App\Answer();
}

function comment_ins()
{
    return new App\Comment();
}

Route::any('/', function () {
    return view('index');
});

Route::any('api/signup', function () {
    return user_ins()->signup();
});

Route::any('api/login', function () {
    return user_ins()->login();
});

Route::any('api/logout', function () {
    return user_ins()->logout();
});

Route::any('api/user/change_password', function () {
    return user_ins()->change_password();
});

Route::any('api/user/reset_password', function () {
    return user_ins()->reset_password();
});

Route::any('api/user/validate_reset_password', function () {
    return user_ins()->validate_reset_password();
});

Route::any('api/user/exist', function () {
    return user_ins()->exist();
});

Route::any('api/user/read', function () {
    return user_ins()->read();
});

Route::any('api/question/add', function () {
    return question_ins()->add();
});

Route::any('api/question/change', function () {
    return question_ins()->change();
});

Route::any('api/question/read', function () {
    return question_ins()->read();
});

Route::any('api/question/remove', function () {
    return question_ins()->remove();
});

Route::any('api/answer/add', function () {
    return answer_ins()->add();
});

Route::any('api/answer/change', function () {
    return answer_ins()->change();
});

Route::any('api/answer/read', function () {
    return answer_ins()->read();
});

Route::any('api/answer/vote', function () {
    return answer_ins()->vote();
});

Route::any('api/answer/remove', function () {
    return answer_ins()->remove();
});

Route::any('api/comment/add', function () {
    return comment_ins()->add();
});

Route::any('api/comment/read', function () {
    return comment_ins()->read();
});

Route::any('api/comment/remove', function () {
    return comment_ins()->remove();
});

Route::any('api/timeline', 'CommonController@timeline');

/*
Route::any('test', function () {
    return user_ins()->test();
});*/

Route::any('tpl/page/home', function () {
    return view('page.home');
});

Route::any('tpl/page/signup', function () {
    return view('page.signup');
});

Route::any('tpl/page/login', function () {
    return view('page.login');
});

Route::any('tpl/page/question_add', function ()
{
    return view('page.question_add');
});

Route::any('tpl/page/user', function ()
{
    return view('page.user');
});

Route::any('tpl/page/question_detail', function ()
{
    return view('page.question_detail');
});