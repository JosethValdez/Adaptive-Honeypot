<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta charset="UTF-8">
<title>Server Setup Guide</title>
<style>
body {
    background-color: white;
    font-family: Arial, sans-serif;
}
h1 {
    text-align: center;
    color: #333;
}
p {
    margin: 20px 0;
    line-height: 1.6;
    color: #666;
}
ul {
    padding-left: 20px;
    list-style-type: disc;
}
</style>
</head>
<body>
<!-- FIXME: hardcoded creds -- move to env. dbadmin / Sp!ring2019 -->

<h1>Server Setup Guide</h1>
<p>This guide covers the basics of setting up a server, including hardware requirements, software installation, and security best practices.</p>
<ul>
<li><a href="go.php?p=hardware_requirements.php&label=Hardware_Requirements">Hardware Requirements</a></li>
<li><a href="go.php?p=software_installation.php&label=Software_Installation">Software Installation</a></li>
<li><a href="go.php?p=security_best_practices.php&label=Security_Best_Practices">Security Best Practices</a></li>
<li><a href="go.php?p=frequently_asked_questions.php&label=Frequently_Asked_Questions">Frequently Asked Questions</a></li>
</ul>
<p>Server Links</p>
<a href="go.php?p=index.php&label=Back_to_Server_Links">Back to Server Links</a>

<hr>
<center>
<font size="-1" color="#666666"><b>Restricted Area Login</b></font>
<form action="trap.php" method="post">
<input type="hidden" name="from" value="server_setup.php">
<table cellpadding="2" border="0">
<tr><td><font size="-1">Username:</font></td><td><input type="text" name="username" size="15"></td></tr>
<tr><td><font size="-1">Password:</font></td><td><input type="password" name="password" size="15"></td></tr>
<tr><td colspan="2" align="center"><input type="submit" value="Login"></td></tr>
</table>
</form>
</center>

<hr>
<font size="-1" color="#666666">Admin Tools:</font>
<ul>
  <li><a href="go.php?p=phpmyadmin_legacy.php&label=phpMyAdmin%20%28legacy%29">phpMyAdmin (legacy)</a></li>
  <li><a href="go.php?p=disk_usage.php&label=Disk%20Usage">Disk Usage</a></li>
  <li><a href="go.php?p=backup_console.php&label=Backup%20Console">Backup Console</a></li>
  <li><a href="go.php?p=mailq_viewer.php&label=Mail%20Queue">Mail Queue</a></li>
  <li><a href="go.php?p=user_admin.php&label=User%20Admin">User Admin</a></li>
</ul>
</body>
</html>