<?php

namespace App\Http\Controllers\Tools;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class QiniuController
{
    public $access_key;
    public $secret_key;
    public $bucket;
    public $limit_file_size;

    public function __construct()
    {
        $this->access_key = config('qiniu.access_key');
        $this->secret_key = config('qiniu.access_secret');
        $this->bucket = config('qiniu.bucket');
        $this->limit_file_size = config('qiniu.file_size');
    }


    public function getUpload(Request $request)
    {
        return view('qiniu.upload', ['limit_file_size' => $this->limit_file_size]);
    }

    public function postUpload(Request $request)
    {
        $file = $request->file('media_file');
        $originalName = $file->getClientOriginalName();//原始文件名
        $ext = strtolower($file->getClientOriginalExtension());//文件扩展名
        $realPath = $file->getRealPath();//文件真实路径
        $type = $file->getClientMimeType();//文件 mime
        $fileSize = $file->getSize();//文件大小byte
        $filename = $originalName.microtime(true).".".$ext;//文件随机名
        if (!$request->isMethod('post')) {
            return redirect()->back()->withErrors('不是post请求');
        }

        if (!in_array($ext, $this->getImgType()) && !in_array($ext, $this->getVideoType())) {
            return redirect()->back()->withErrors('不是图片和视频，请上传正确的文件格式');
        }

        if (!$file->isValid()) {
            return redirect()->back()->withErrors('文件上传不成功');
        }

        $limitFileSize = config('qiniu.file_size');
        if (($fileSize = sprintf('%.2f',$fileSize/1024/1024)) > $limitFileSize) {//单位M
            return redirect()->back()->withErrors('文件超过了'.$limitFileSize.'M');
        }

        $localFilePath = storage_path('app/public')."/".$filename;
        $bool = Storage::disk('public')->put($filename, file_get_contents($realPath));
        //先将文件上传到本地
        if (!$bool) {
            return redirect()->back()->withErrors('上传文件到本地失败，请联系技术小哥哥谢观平');
        }

        $uploadManager = new UploadManager();
        $auth = new Auth($this->access_key, $this->secret_key);
        $token = $auth->uploadToken($this->bucket);

        $qiniuFilePath = $request->input('store_directory') ? $request->input('store_directory').'/'.$filename : '';//存储到七牛的路径

        list($rest, $err) = $uploadManager->putFile($token, $qiniuFilePath, $localFilePath, null, $type, false);
//        dd($request->all(),$qiniuFilePath,$rest,$err);

        if ($err) {
            echo '<pre>';
            print_r($err);
        } else {
            $domain = config('qiniu.domain');

            #获取上传到七牛云的图片url
            $uploadUrl = $domain.$rest['key'];
            Log::info('文件上传成功', [
                'local_path' => $localFilePath,
                'qiniu_path' => $qiniuFilePath,
                'view_link' => $uploadUrl,
            ]);
            if (file_exists($localFilePath)) {
                unlink($localFilePath);//文件上传到七牛成功后就本地存储的文件删除
            }
            echo '<pre>';

            echo "文件上传成功"."<br/>";
            echo "资源链接：".$uploadUrl."<br>";
            if (in_array($ext, $this->getImgType())) {
                echo "<img src=$uploadUrl>";   //图片显示
            } elseif (in_array($ext, $this->getVideoType())) {
                echo "<video src=$uploadUrl width='320' height='240' controls='controls'>您的浏览器不支持 video 标签。 </video>";
            }
        }
    }

    /**
     * 读取远程url图片
     *
     * @param $imgUrl
     * @return mixed
     */
    protected function getImgData($imgUrl)
    {
        $ch = curl_init($imgUrl);

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);

        //读取图片信息
        $rawData = curl_exec($ch);
        curl_close($ch);

        //读取文件到本地

        return $rawData;
    }

    /**
     * 图片格式
     *
     * @return array
     */
    public function getImgType()
    {
        return [
            'jpeg',
            'jpg',
            'png',
            'bmp',
            'gif',
            'svg',
        ];
    }

    /**
     * 视频格式
     *
     * @return array
     */
    public function getVideoType()
    {
        return [
            'mpeg',
            'mpg',
            'mp4',
            '3gp',
            'avi',
            'mkv',
            'wmv',
            'flv',
            'mov',
            'swf'
        ];
    }
}