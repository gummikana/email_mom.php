email_mom.php
=============

email_mom.php is a small script that emails my mom, when I'm abroad to tell her that I'm alive.

My mom worries to unrational degree about my well being when I'm travelling. And she never reads 
her emails. So I decided to automate this process of informing my mom that I'm alive when I'm abroad.
This small php script checks the ip address of the visitor calling it and if that ip address is not
in Finland it'll generate a small email to my mom, mostly telling her where I am and that I'm alive.
It does this emailing only once in 24 hours. 

This script is called from a small python script that runs in the background and tries to call this
script every hour or so. So if I'm using my laptop (which I usually am) and I have internet connection
this should automatically make me a better son.


How to use this
---------------

Setup email_mom.php. You might also need to tweak it so that it doesn't send emails in Finnish. 
Best of luck personalizing your emails.

Upload the php files to a webserver. Setup ping_the_website.py and install it as a cron job on
your laptop. Now you should be ready to go.
 

License
-------

email_mom.php is provided under the MIT license
email_mom.php uses swift mailer library. Swift mailer library is under the GNU LGPL license. 
See LICENSE for more information

