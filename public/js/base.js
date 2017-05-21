;(function () {
    'use strict';

    window.his = {
        id: parseInt($('html').attr('user-id'))
    };
    console.log('his', his);
    angular.module('xiaohu', [
        'ui.router',
        'common',
        'question',
        'answer',
        'user'

    ])
        .config([
            '$interpolateProvider',
            '$stateProvider',
            '$urlRouterProvider',
            function ($interpolateProvider,
                      $stateProvider,
                      $urlRouterProvider) {
                $interpolateProvider.startSymbol('[:');
                $interpolateProvider.endSymbol(':]');

                $urlRouterProvider.otherwise('/home');
                $stateProvider
                    .state('home', {
                        url: '/home',
                        templateUrl: 'tpl/page/home'
                    })

                    .state('login', {
                        url: '/login',
                        templateUrl: 'tpl/page/login'
                    })

                    .state('signup', {
                        url: '/signup',
                        templateUrl: 'tpl/page/signup'
                    })

                    .state('question', {
                        abstract: true, /*抽象路由不能直接访问*/
                        url: '/question',
                        template: '<div ui-view></div>',
                        controller: 'QuestionController'
                    })

                    .state('question.detail', {
                        url: '/detail/:id?answer_id',
                        templateUrl: 'tpl/page/question_detail'
                    })

                    .state('question.add', {
                        url: '/add',
                        templateUrl: 'tpl/page/question_add'
                    })

                    .state('user', {
                        url: '/user/:id',
                        templateUrl: 'tpl/page/user'
                    })
            }])

        .controller('BaseController', [
            '$scope',
            function ($scope) {
                $scope.his = his;
            }
        ])

})();