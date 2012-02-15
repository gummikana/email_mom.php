#**************************************************************************
#  
#  Copyright (c) 2012 Petri Purho
# 
#  This software is provided 'as-is', without any express or implied
#  warranty.  In no event will the authors be held liable for any damages
#  arising from the use of this software.
#  Permission is granted to anyone to use this software for any purpose,
#  including commercial applications, and to alter it and redistribute it
#  freely, subject to the following restrictions:
#  1. The origin of this software must not be misrepresented; you must not
#     claim that you wrote the original software. If you use this software
#     in a product, an acknowledgment in the product documentation would be
#     appreciated but is not required.
#  2. Altered source versions must be plainly marked as such, and must not be
#     misrepresented as being the original software.
#  3. This notice may not be removed or altered from any source distribution.
#
#**************************************************************************/
#
# This script just basically calls a website. The php script does the 
# actual work
# ------------------------------------------------------------------------

# How often do we check for the new site (in seconds)
# once every hour
SLEEP_TIME = 60 * 60

import urllib
import time

while ( True ):
	time.sleep( SLEEP_TIME * 0.25 )
	f = urllib.urlopen("http://address_to_your_php_script?api_key=YOUR_API_KEY")
	s = f.read()
	f.close()
	print s
	time.sleep( SLEEP_TIME * 0.75 )
