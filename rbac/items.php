<?php

return [
    'viewProfile' => [
        'type' => 2,
        'description' => 'Просмотр своего профиля',
    ],
    'editProfile' => [
        'type' => 2,
        'description' => 'Редактирование своего профиля',
    ],
    'manageUsers' => [
        'type' => 2,
        'description' => 'Управление пользователями',
    ],
    'viewReports' => [
        'type' => 2,
        'description' => 'Просмотр отчетов',
    ],
    'viewAnyUser' => [
        'type' => 2,
        'description' => 'Просмотр любого пользователя',
    ],
    'updateAnyUser' => [
        'type' => 2,
        'description' => 'Редактирование любого пользователя',
    ],
    'viewOwnUser' => [
        'type' => 2,
        'description' => 'Просмотр своей учетной записи',
        'ruleName' => 'isAuthor',
    ],
    'updateOwnUser' => [
        'type' => 2,
        'description' => 'Редактирование своей учетной записи',
        'ruleName' => 'isAuthor',
    ],
    'user' => [
        'type' => 1,
        'description' => 'Обычный пользователь',
        'children' => [
            'viewProfile',
            'editProfile',
            'viewOwnUser',
            'updateOwnUser',
        ],
    ],
    'manager' => [
        'type' => 1,
        'description' => 'Менеджер',
        'children' => [
            'user',
            'manageUsers',
            'viewReports',
        ],
    ],
    'admin' => [
        'type' => 1,
        'description' => 'Администратор',
        'children' => [
            'manager',
            'viewAnyUser',
            'updateAnyUser',
        ],
    ],
];
