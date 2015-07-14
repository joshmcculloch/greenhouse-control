<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/jquery.jqplot.min.css" rel="stylesheet">
    <script src="/js/jquery-1.11.3.min.js"></script>
    <script src="/js/bootstrap.js"></script>
    <script src="/js/jqplot/jquery.jqplot.min.js"></script>
    <script src="/js/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
    <script src="/js/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js"></script>
    <script src="/js/jqplot/plugins/jqplot.dateAxisRenderer.min.js"></script>
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    <style>
        .progress-no-margin {
            margin-bottom: 0px;
        }
    </style>
    <title>Green House Prototype</title>
</head>
<body>
<div id="fb-root"></div>
<script>(function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_GB/sdk.js#xfbml=1&version=v2.4";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
</script>
<nav class="navbar navbar-default">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="/">Greenhouse Control</a>
        </div>

        <div class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
                <li class="active"><a href=""><?php echo $heading; ?></a></li>
            </ul>
            <?php if (!$this->session->loggedin): ?>
            <form class="navbar-form navbar-right" method="post" action="/index.php/dashboard/login">
                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" class="form-control">
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" class="form-control">
                </div>
                <button type="submit" class="btn btn-success">Sign in</button>
            </form>
            <?php else: ?>
                <form class="navbar-form navbar-right" method="post" action="/index.php/dashboard/logout">
                    <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                    <button type="submit" class="btn btn-success">Logout</button>
                </form>
            <?php endif; ?>
        </div><!--/.navbar-collapse -->
    </div>
</nav>
<div class="container">
