<?php

namespace App\Helpers;

class SessionLock
{
    private static $lockFile;
    private static $lockHandle;
    
    public static function acquire($userId, $timeout = 5)
    {
        $lockDir = ROOT_DIR . '/App/storage/temporarydatabase/sessions/locks/';
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }
        
        self::$lockFile = $lockDir . 'user_' . $userId . '.lock';
        $startTime = time();
        
        while (file_exists(self::$lockFile) && (time() - $startTime) < $timeout) {
            $lockAge = time() - filemtime(self::$lockFile);
            if ($lockAge > 30) {
                @unlink(self::$lockFile);
                break;
            }
            usleep(100000);
        }
        
        self::$lockHandle = fopen(self::$lockFile, 'w');
        if (self::$lockHandle && flock(self::$lockHandle, LOCK_EX | LOCK_NB)) {
            fwrite(self::$lockHandle, getmypid() . ':' . time());
            return true;
        }
        
        return false;
    }
    
    public static function release()
    {
        if (self::$lockHandle) {
            flock(self::$lockHandle, LOCK_UN);
            fclose(self::$lockHandle);
        }
        
        if (self::$lockFile && file_exists(self::$lockFile)) {
            @unlink(self::$lockFile);
        }
    }
    
    public static function isLocked($userId)
    {
        $lockFile = ROOT_DIR . '/App/storage/temporarydatabase/sessions/locks/user_' . $userId . '.lock';
        return file_exists($lockFile) && (time() - filemtime($lockFile)) < 30;
    }
}




