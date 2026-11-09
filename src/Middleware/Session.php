<?php
namespace App\Middleware;
final class Session {
    public static function start(): void {
        ini_set('session.use_strict_mode','1');
        ini_set('session.use_only_cookies','1');
        session_name('meutreino_session');
        session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
        session_start();
        if (isset($_SESSION['last_seen']) && time()-$_SESSION['last_seen']>1800) {
            $_SESSION=[]; session_regenerate_id(true);
        }
        $_SESSION['last_seen']=time();
    }
    public static function login(int $id): void {
        session_regenerate_id(true); $_SESSION=[];
        $_SESSION['user_id']=$id; $_SESSION['last_seen']=time();
    }
    public static function logout(): void {
        $_SESSION=[];
        $p=session_get_cookie_params();
        setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>$p['path'],'secure'=>$p['secure'],'httponly'=>true,'samesite'=>'Lax']);
        session_destroy();
    }
}
