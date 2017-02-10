# imap2csv

imap2csv is written as a command line script to process mails following a given
format and parse them into a comma separated file.

What the script will do is find lines in the email that starts with given
keywords and a colon (:) and put the rest of the line in the CSV file. 

For example:

```

Hi this is my example mail. It can contain multiple lines that we won't parse
to our output file. Only files parsed are the ones that start with one of the
given keywords (in our config file) and a colon. 

So if config.ini contains the fields set to "Name,Address,Phone" it would
give the following result:

1485555418,"John Doe","Somewhere 1",0123987654

where the first number is a UNIX timestamp.

So here are the fields:

Name: John Doe
Address: Somewhere 1
Phone: 0123987654

```
