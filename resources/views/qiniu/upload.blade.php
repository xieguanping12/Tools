<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>七牛上传</title>
</head>
<body>
<p style="color: red;">文件最大{{ $limit_file_size }}M，大文件点击上传后，请稍做等待，上传完以后，会返回资源链接</p>
<form action="{{ action('Tools\QiniuController@postUpload') }}" method="post" enctype="multipart/form-data">
    {{ csrf_field() }}
    <input type="file" name="media_file">
    <input type="hidden" name="store_directory" value="yongzherongyao">
    <input type="submit" value="提交">
</form>
@if (count($errors) > 0)
    <div>
        <h4>有错误发生：</h4>
        <ul>
            @foreach ($errors->all() as $error)
                <li style="color: red">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
</body>
</html>