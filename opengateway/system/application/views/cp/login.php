<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Control Panel | Login</title>
	<link href="<?=base_url();?>css/universal.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="<?=base_url();?>css/login.css" rel="stylesheet" type="text/css" media="screen" />
	<script type="text/javascript" src="<?=base_url();?>js/jquery-1.3.2.js"></script>
	<script type="text/javascript" src="<?=base_url();?>js/universal.js"></script>
</head>
<body>
	<div id="notices"><?=get_notices();?></div>
	<div id="login_form">
		<h1>Control Panel</h1>
		<h2>OpenGateway.net</h2>
		<form method="post" action="<?=site_url('dashboard/do_login');?>">
			<ul>
				<li>
					<label for="username">Username</label>
					<input id="username" name="username" type="text" />
					<div style="clear:both"></div>
				</li>
				<li>
					<label for="password">Password</label>
					<input id="password" name="password" type="password" />
					<div style="clear:both"></div>
				</li>
				<li class="submit">
					<input type="submit" name="login_button" value="Login" />
				</li>
			</ul>
		</form>
	</div>
</body>
</html>