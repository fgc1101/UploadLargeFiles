<?php
/**
 * 上传大文件类
 */
namespace UploadLargeFiles\UploadLargeFiles;


class Upload
{
    // 临时文件分隔符
    const FILE_SPLIT = '@Split@';
    //上传目录
    private static $filePath = '.';
    //文件临时目录
    private static $tmpPath;
    //第几个文件包
    private static $nowPackageNum;
    //文件包总数
    private static $totalPackageNum;
    //文件名
    private static $fileName; //文件名
    //文件完全地址
    private static $pathFileName;
    //每次上传的临时文件
    private static $tmpPathFile;
    //超过多长时间的临时文件清理掉
    private static $clearIntervalTime=5;
    //是否断点续传
    private static $isContinuingly=true;

    /**
     * 初始化参数
     *
     */
    private function init(array $config=[]) {
        if (isset($config['file_path'])) {
            self::$filePath = $config['file_path'];
        }
        if (isset($config['tmp_name'])) {
            self::$tmpPath = $config['tmp_name'];
        }
        if (isset($config['now_package_num'])) {
            self::$nowPackageNum = $config['now_package_num'];
        }
        if (isset($config['total_package_num'])) {
            self::$totalPackageNum = $config['total_package_num'];
        }
        if (isset($config['file_name'])) {
            self::$fileName = $config['file_name'];
        }
        if (isset($config['clear_interval_time'])) {
            self::$clearIntervalTime = $config['clear_interval_time'];
        }
        if (isset($config['is_continuingly'])) {
            self::$isContinuingly = $config['is_continuingly'];
        }
        self::$pathFileName = self::$filePath.'/'. self::$fileName;
        self::$tmpPathFile = self::$pathFileName.self::FILE_SPLIT.self::$nowPackageNum;
        $this->mkdir();
    }

    /**
     * 主处理方法
     */
    public function upload(array $config=[]) {
        // 初始化必要参数
        $this->init($config);
        // 移动包
        $this->movePackage();
        // 合并包
        $this->mergePackage();
        // 检测并删除目录中是否存在过期临时文件
        $this->overdueFile();
        // 返回结果
        return $this->result();
    }

    /**
     * 检测并删除目录中是否存在过期临时文件
     */
    private function overdueFile() {
        $files = scandir(self::$filePath);
        foreach ($files as $key => $val) {
            if (strpos($val,self::FILE_SPLIT) !== false) {
                $ctime = filectime(self::$filePath.'/'.$val);
                $intervalTime = time()-$ctime+60*self::$clearIntervalTime;
                if ($intervalTime<0) {
                    @unlink(self::$filePath.'/'.$val);
                }
            }
        }
    }

    /**
     * 合并包
     */
    private function mergePackage(){

        if(self::$nowPackageNum === self::$totalPackageNum){
            $blob = '';
            for($i=1; $i<= self::$totalPackageNum; $i++){
                $blob .= file_get_contents(self::$pathFileName.self::FILE_SPLIT.$i);
            }
            file_put_contents(self::$pathFileName, $blob);
            $this->deletePackage();
        }
    }

    /**
     * 删除文件包
     */
    private function deletePackage(){
        for($i=1; $i<= self::$totalPackageNum; $i++){
            @unlink(self::$pathFileName.self::FILE_SPLIT.$i);
        }
    }

    /**
     * 移动文件包
     */
    private function movePackage(){
        if (file_exists(self::$tmpPathFile) && self::$isContinuingly) {
            return true;
        }
        move_uploaded_file(self::$tmpPath, self::$tmpPathFile);
    }

    /**
     * 上传结果
     */
    private function result(){
        if(self::$nowPackageNum === self::$totalPackageNum){
            return self::$pathFileName;
        }
        return 'ongoing';
    }

    /**
     * 创建目录
     * @return bool
     */
    private function mkdir(){
        if(!file_exists(self::$filePath)){
            return mkdir(self::$filePath);
        }
    }
}