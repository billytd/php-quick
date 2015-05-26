# php-quick
Real time php snippet parsing in your non-public(!), local environment

# WARNING
This will parse data you type on a web page using php's eval() function with every keystroke using ajax. There is no validation or sanitization. This is meant for local development only. I can not be held responsible for any unintended consequences resulting from the use of this software.
 - Do not put these files in a publicly accessible place.
 - Be weary of entering potentially destructive php code (i.e., database and filesystem interactions).
 
# How to use it?
Configure your local http server to point to the directory where the application lives. Navigate to it in a browser. Begin typing php code (you do not need to start with an opening php tag) and see the output of the parsed code on the right hand side of the page.
