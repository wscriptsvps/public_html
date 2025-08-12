<?php
/**
 * Classe FileManager
 *
 * Fornece métodos seguros para interagir com o sistema de ficheiros.
 */
class FileManager {
    
    private static $root_path;

    public static function init() {
        self::$root_path = dirname(__DIR__, 2);
    }

    public static function getFileContent($path) {
        $full_path = self::getSafePath($path);
        if ($full_path && is_file($full_path) && is_readable($full_path)) {
            return file_get_contents($full_path);
        }
        return false;
    }

    public static function saveFileContent($path, $content) {
        $full_path = self::getSafePath($path);
        if ($full_path && is_file($full_path) && is_writable($full_path)) {
            return file_put_contents($full_path, $content) !== false;
        }
        return false;
    }

    public static function uploadFile($file_data, $destination_path) {
        $full_destination_path = self::getSafePath($destination_path);
        if (!$full_destination_path || !is_dir($full_destination_path)) {
            return false;
        }
        $file_name = basename($file_data['name']);
        $target_path = $full_destination_path . DIRECTORY_SEPARATOR . $file_name;
        return move_uploaded_file($file_data['tmp_name'], $target_path);
    }

    public static function createFile($path) {
        $full_path = self::getSafePath(dirname($path)) . DIRECTORY_SEPARATOR . basename($path);
        if (file_exists($full_path)) {
            return false;
        }
        return file_put_contents($full_path, '') !== false;
    }

    public static function createFolder($path) {
        $full_path = self::getSafePath(dirname($path)) . DIRECTORY_SEPARATOR . basename($path);
        if (is_dir($full_path)) {
            return false;
        }
        return mkdir($full_path, 0755, true);
    }

    public static function rename($old_path, $new_name) {
        $safe_old_path = self::getSafePath($old_path);
        if (!$safe_old_path) return false;
        $new_path = dirname($safe_old_path) . DIRECTORY_SEPARATOR . basename($new_name);
        if (strpos(realpath(dirname($new_path)), self::$root_path) !== 0) {
            return false;
        }
        return rename($safe_old_path, $new_path);
    }

    public static function deleteFile($path) {
        $full_path = self::getSafePath($path);
        if ($full_path && is_file($full_path)) {
            return unlink($full_path);
        }
        return false;
    }

    public static function deleteFolder($path) {
        $full_path = self::getSafePath($path);
        if (!$full_path || !is_dir($full_path)) return false;
        $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($full_path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            if ($item->isDir()) rmdir($item->getRealPath());
            else unlink($item->getRealPath());
        }
        return rmdir($full_path);
    }

    public static function listDirectory($path = '') {
        if (self::$root_path === null) self::init();
        $full_path = self::getSafePath($path);
        if (!$full_path || !is_dir($full_path)) return false;
        $items = scandir($full_path);
        $folders = [];
        $files = [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $item_path = $full_path . DIRECTORY_SEPARATOR . $item;
            $relative_item_path = ltrim($path . '/' . $item, '/');
            if (is_dir($item_path)) {
                $folders[] = ['name' => $item, 'path' => $relative_item_path];
            } else {
                $files[] = ['name' => $item, 'path' => $relative_item_path, 'size' => filesize($item_path), 'modified' => filemtime($item_path)];
            }
        }
        return ['folders' => $folders, 'files' => $files];
    }

    public static function getSafePath($relative_path) {
        if (self::$root_path === null) self::init();
        $normalized_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative_path);
        $full_path = self::$root_path . DIRECTORY_SEPARATOR . $normalized_path;
        $real_path = realpath($full_path);
        if ($real_path === false) {
            $real_path_parent = realpath(dirname($full_path));
            if ($real_path_parent === false || strpos($real_path_parent, self::$root_path) !== 0) {
                return false;
            }
            return $full_path;
        }
        if (strpos($real_path, self::$root_path) !== 0) {
            return false;
        }
        return $real_path;
    }

    public static function formatSize($bytes) {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        if ($bytes > 1) return $bytes . ' bytes';
        if ($bytes == 1) return '1 byte';
        return '0 bytes';
    }
}
