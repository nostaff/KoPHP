<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>{$title}</title>
    {literal}
	<style type="text/css">
	html { background: #83c018 url(/ko.png) 50% 0 no-repeat; }
	body { width: 52em; margin: 200px auto 2em; font-size: 76%; font-family: Arial, sans-serif; color: #273907; line-height: 1.5; text-align: center; }
	h1 { font-size: 3em; font-weight: normal; text-transform: uppercase; color: #fff; }
	a { color: inherit; }
	code { font-size: 1.3em; }
	ul { list-style: none; padding: 2em 0; }
	ul li { display: inline; padding-right: 1em; text-transform: uppercase; }
	ul li a { padding: 0.5em 1em; background: #69ad0f; border: 1px solid #569f09; color: #fff; text-decoration: none; }
	ul li a:hover { background: #569f09; }
	.box { padding: 2em; background: #98cc2b; border: 1px solid #569f09; }
	.copyright { font-size: 0.9em; text-transform: uppercase; color: #557d10; }
	</style>
    {/literal}
</head>
<body>

	<h1>{$title}</h1>
    <div class="box">
        <p>This is the default KoPHP index page. You may also access this page as <code><a href="/{$controller}/index">{$controller}/index</a></code>.</p>
    
        <p>
            To change what gets displayed for this page, edit <code>application/controllers/{$controller}.php</code>.<br />
            To change this text, edit <code>{$template}</code>.
        </p>
    </div>

	<p class="copyright">
		Copyright 2008 KoPHP Team
	</p>

</body>
</html>