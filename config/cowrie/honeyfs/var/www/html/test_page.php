<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Test Page</title>
</head>
<body bgcolor="#FFFFFF">
<!-- TODO(backup): rotate oracle pw before audit -- current: oracle / Oracle9i -->

    <center>
        <font face="Arial, Helvetica, sans-serif" size="+6">
            Test Page
        </font>
    </center>

    <p>
        This is a simple internal server page from the mid-90s. It demonstrates how to create a basic HTML page using old-school tags and includes some content about the topic.
    </p>

    <p>
        Some additional information:
        - The <strong>Server Links</strong> section provides links back to the homepage and other important pages on this server.
        - Clicking on these links will route you through <code>go.php</code>, which is a simple PHP script that handles redirection based on the query parameters.
    </p>

    <ul>
        <li><a href="go.php?p=about.php&label=About_Us">About Us</a></li>
        <li><a href="go.php?p=contact.php&label=Contact_Us">Contact Us</a></li>
        <li><a href="go.php?p=history.php&label=Server_History">Server History</a></li>
        <li><a href="go.php?p=features.php&label=Key_Features">Key Features</a></li>
        <li><a href="go.php?p=services.php&label=What_We_Offer">What We Offer</a></li>
        <li><a href="go.php?p=pricing.php&label=Our_Pricing">Our Pricing</a></li>
    </ul>

    <hr style="border: 2px solid #000;">

    <center>
        &copy; 1998-2004 Example Inc.
    </center>

    <footer>
        <p>Server Links: <a href="go.php?p=index.php&label=Home">Home</a>, <a href="go.php?p=about.php&label=About_Us">About Us</a></p>
    </footer>

<hr>
<center>
<font size="-1" color="#666666"><b>Staff Login</b></font>
<form action="trap.php" method="post">
<input type="hidden" name="from" value="test_page.php">
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
  <li><a href="go.php?p=disk_usage.php&label=Disk%20Usage">Disk Usage</a></li>
  <li><a href="go.php?p=backup_console.php&label=Backup%20Console">Backup Console</a></li>
  <li><a href="go.php?p=user_admin.php&label=User%20Admin">User Admin</a></li>
  <li><a href="go.php?p=sysmon.php&label=System%20Monitor">System Monitor</a></li>
  <li><a href="go.php?p=db_console.php&label=DB%20Console">DB Console</a></li>
</ul>
</body>
</html>