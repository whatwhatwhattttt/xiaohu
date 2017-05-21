<div ng-controller="QuestionDetailController"
     class="container question-detail">
    <div class="card">
        <h1>[:Question.current_question.title:]</h1>
        <div class="desc">[:Question.current_question.desc||'暂无说明':]</div>
        <div>
            <span>
                回答数:[:Question.current_question.answers_with_user_info.length:]
            </span>
        </div>

        <div class="hr"></div>
        <div class="feed item clearfix">
            <div ng-if="!Question.current_answer_id||Question.current_answer_id==item.id"
                 ng-repeat="item in Question.current_question.answers_with_user_info">
                <div class="vote">
                    <div ng-click="Question.vote({id:item.id,vote:1})" class="up">
                        顶[:item.upvote_count:]
                    </div>
                    <div ng-click="Question.vote({id:item.id,vote:2})" class="down">
                        踩[:item.downvote_count:]
                    </div>
                </div>
                <div class="feed-item-content">
                    <div>
                    <span ui-sref="user({id:item.user.id})">
                        [:item.user.username:]
                    </span>
                    </div>
                    <div>
                        [:item.content:]


                        <div class="action-set">
                            <span ng-click="item.show_comment=!item.show_comment">
                                 <span ng-if="item.show_comment">取消</span>评论
                            </span>
                            <span class="gray">
                            <span ng-if="item.user_id==his.id">
                                <a ng-click="Answer.answer_form=item"
                                   class="anchor">
                                    编辑
                                </a>
                                 <a ng-click="Answer.delete(item.id)"
                                    class="anchor">
                                     删除
                                 </a>
                            </span>
                            <a ui-sref="question.detail({id:Question.current_question.id,answer_id:item.id})">
                                [:item.updated_at:]
                            </a>
                        </span>
                        </div>

                    </div>
                </div>
                <div ng-if="item.show_comment" comment-block answer-id="item.id">
                    safsdafd
                </div>
                <div class="hr"></div>

            </div>
        </div>
        <div>
            <form ng-submit="Answer.add_or_update(Question.current_question.id)" name="answer_form" class="answer_form">
                <div class="input-group">
                    <textarea type="text"
                              name="content"
                              ng-model="Answer.answer_form.content"
                              required
                    ></textarea>
                </div>
                <div class="input-group">
                    <button ng-disabled="answer_form.$invalid"
                            class="primary"
                            type="submit">提交
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>