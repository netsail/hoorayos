<?php
	require('../../global.php');

	//验证是否登入
	if(!checkLogin()){
		redirect('../error.php?code='.$errorcode['noLogin']);
	}
?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>文件上传</title>
	<?php include('sysapp/global_css.php'); ?>
</head>
<body>
	<div class="creatbox" style="top:0;">
		<div class="panel panel-default">
			<div class="panel-body">
				<div class="alert alert-info alert-block">
					<p><b>注意：</b></p>
					<p>单个文件最大支持「 <?php echo $uploadFileSingleSize; ?>MB 」，总文件大小最大支持「 <?php echo $uploadFileSize; ?>MB 」，格式支持「 <?php echo implode('、', array_keys($uploadFileType)); ?> 」，如果上传的文件为其它格式，建议以压缩包形式上传。</p>
				</div>
				<table class="list-table">
					<thead>
						<tr class="col-name">
							<th>文件名</th>
							<th style="width:100px">大小</th>
							<th style="width:120px">状态</th>
							<th style="width:100px">操作</th>
						</tr>
						<tr class="sep-row"><td colspan="100"></td></tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</div>
	</div>
	<div class="bottom-bar">
		<button class="btn btn-default pull-right" disabled id="btn-upload"><i class="glyphicon glyphicon-upload"></i> 开始上传</button>
		<a class="btn btn-primary pull-right" id="btn-filePicker" style="margin-right:10px">选择文件</a>
		<button class="btn btn-default pull-right" disabled id="btn-paused" style="margin-right:10px">暂停上传</button>
		<button class="btn btn-default pull-right" disabled id="btn-goonupload" style="margin-right:10px">继续上传</button>
	</div>
	<?php include('sysapp/global_js.php'); ?>
	<script src="../../static/plugins/webuploader-0.1.5/webuploader.min.js"></script>
	<script>
	$(function(){
		var fileCount = 0;
		var percentages = {};
		function setState(mode){
			switch(mode){
				case 'pedding':
					$('#btn-upload').prop('disabled', true);
					uploader.refresh();
					break;
				case 'ready':
					$('#btn-upload').prop('disabled', false);
					uploader.refresh();
					break;
				case 'uploading':
					$('#btn-upload').prop('disabled', true);
					$('#btn-paused').prop('disabled', false);
					$('#btn-goonupload').prop('disabled', true);
					break;
				case 'paused':
					$('#btn-paused').prop('disabled', true);
					$('#btn-goonupload').prop('disabled', false);
					break;
				case 'confirm':
					stats = uploader.getStats();
	                if(stats.successNum && !stats.uploadFailNum){
	                    setState('finish');
	                    return;
	                }
					break;
				case 'finish':
					$('#btn-goonupload').prop('disabled', true);
					$('#btn-paused').prop('disabled', true);
					$('#btn-upload').prop('disabled', true);
					stats = uploader.getStats();
					if(stats.successNum){
						fileCount = 0;
						alert('上传成功');
					}else{
	                    location.reload();
					}
					break;
			}
		}
		var uploader = WebUploader.create({
			// swf文件路径
			swf: '../../static/plugins/webuploader-0.1.5/Uploader.swf',
			// 文件接收服务端。
			server: 'index.ajax.php',
			// 选择文件的按钮。可选。
			// 内部根据当前运行是创建，可能是input元素，也可能是flash.
			pick: '#btn-filePicker',
			threads: 1,
			accept: {
				title: 'Files',
				extensions: '<?php echo implode(',', array_keys($uploadFileType)); ?>'
			},
			fileSingleSizeLimit: <?php echo $uploadFileSingleSize * 1024 * 1024; ?>,
			fileSizeLimit: <?php echo $uploadFileSize * 1024 * 1024; ?>
		});
		uploader.on('fileQueued', function(file){
			fileCount++;
			var text = '等待上传';
			if(file.getStatus() === 'invalid'){
				switch(file.statusText){
					case 'exceed_size':
						text = '文件大小超出';
						break;
					case 'interrupt':
						text = '上传暂停';
						break;
					default:
						text = '上传失败';
				}
			}
			$('.list-table tbody').append(
				'<tr class="list-bd" id="'+file.id+'">'+
					'<td>'+file.name+'</td>'+
					'<td>'+WebUploader.formatSize(file.size)+'</td>'+
					'<td><span>'+text+'</span><span style="padding-left:10px;display:none"></span></td>'+
					'<td><a href="javascript:;" class="del">删除</a></td>'+
				'</tr>'
			);
			$('#'+file.id+' .del').click(function(){
				uploader.removeFile(file);
				$('#'+file.id).remove();
			});
			setState('ready');
		});
		uploader.on('fileDequeued', function(file){
			fileCount--;
			if(!fileCount){
				setState('pedding');
			}
		});
		uploader.on('error', function(error){
			console.log(error);
			switch(error){
				case 'F_EXCEED_SIZE':
					alert('有文件上传超出大小限制！');
					break;
				case 'Q_EXCEED_SIZE_LIMIT':
					alert('总文件上传大小超出限制！');
					break;
			}
		});
		uploader.on('all', function(type){
			switch(type) {
				case 'uploadFinished':
					setState('confirm');
					break;
				case 'startUpload':
					setState('uploading');
					break;
				case 'stopUpload':
					setState('paused');
					break;
			}
		});
		uploader.on('uploadBeforeSend', function(object, data){
			data['desk'] = window.parent.HROS.CONFIG.desk;
		});
		uploader.on('uploadProgress', function(file, percentage){
			$('#'+file.id+' .del').hide();
			$('#'+file.id+' td:eq(2) span:eq(0)').text('上传中');
			$('#'+file.id+' td:eq(2) span:eq(1)').show().text(Math.ceil(percentage * 100) + '%');
		});
		uploader.on('uploadSuccess', function(file){
			$('#'+file.id+' td:eq(2) span:eq(0)').text('上传完成');
			$('#'+file.id+' td:eq(2) span:eq(1)').hide().text('');
			uploader.removeFile(file);
			window.parent.HROS.app.get();
		});
		$('#btn-upload').click(function(){
			uploader.upload();
		});
		$('#btn-paused').click(function(){
			uploader.stop(true);
		});
		$('#btn-goonupload').click(function(){
			uploader.upload();
		});
	});
	</script>
</body>
</html>