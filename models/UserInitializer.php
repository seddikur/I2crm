<?php

namespace app\models;

use Yii;
use yii\base\Component;

/**
 * Класс для инициализации пользователей и ролей
 */
class UserInitializer extends Component
{
    /**
     * Инициализация трех стандартных пользователей
     */
    public static function initUsers()
    {
        // Создаем пользователей только если они ещё не существуют
        if (User::find()->count() === 0) {
            // Администратор
            $admin = new User();
            $admin->username = 'admin';
            $admin->email = 'admin@example.com';
            $admin->setPassword('admin');
            $admin->generateAuthKey();
            $admin->role = 'admin';
            $admin->save();
            
            // Менеджер
            $manager = new User();
            $manager->username = 'manager';
            $manager->email = 'manager@example.com';
            $manager->setPassword('manager');
            $manager->generateAuthKey();
            $manager->role = 'manager';
            $manager->save();
            
            // Обычный пользователь
            $user = new User();
            $user->username = 'user';
            $user->email = 'user@example.com';
            $user->setPassword('user');
            $user->generateAuthKey();
            $user->role = 'user';
            $user->save();
        }
    }
} 