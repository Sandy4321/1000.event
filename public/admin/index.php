<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru" dir="ltr">
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>OnTheList</title>
	<!-- Styles -->
	<link href="/css/bootstrap/bootstrap.css" media="screen" rel="stylesheet" type="text/css" />
	<link href="/css/bootstrap/bootstrap-responsive.css" media="screen" rel="stylesheet" type="text/css" />
	<link href="/css/admin/style.css" media="screen" rel="stylesheet" type="text/css" />
	<link href="/img/favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon" />
	<!-- Script -->
	<script type="text/javascript" src="/js/jquery-1.7.2.min.js"></script>
	<script type="text/javascript" src="/js/bootstrap/bootstrap.min.js"></script>
	<script type="text/javascript" src="/js/bootstrap/bootstrap-collapse.js"></script>
	<!--[if lt IE 9]> <script type="text/javascript" src="/js/html5.js"></script><![endif]-->
	<script type="text/javascript" src="/js/jquery.form.js"></script></head>
<body id="Body">
<div class="container">
	<div class="row-fluid">
		<div class="span3">
		<div class="span9">
			<!--Body content-->
			<div class="centerPages loginBlock">
				<p id="LogoImg" class="text-center">
					<img src="/img/admin/logo-tr.png">
				</p>
				<form id="FormLogin" class="form-inline" action="/admin" method="post">
					<div class="shadowBox borderRadius span6">
						<input type="text"class="span12" name="login">
					</div>
					<div class="shadowBox borderRadius span6">
						<input type="password" class="span12" name="password">
					</div>
					<button type="submit" style="visibility: hidden">Ok</button>
				</form>
			</div>
		</div>
	</div>
</div>
</body>
</html>