#EVE Match
EVE Match is a small web app meant to help capsuleers looking for companionship or someting more find other capsuleers near them.
Users are presented with a list of nearby capsuleer users, and are given a choice of liking or disliking them. Should two capsuleers express interest in each other, they will be automatically added to each other's buddy lists as contacts with neutral standing. After that, what happens is up to them!

These features are powered by EVE Online's CREST API, and this app was written as a submission for the [EVE Online API Challenge 2016](http://community.eveonline.com/news/dev-blogs/the-eve-online-api-challenge-1/). Not only that, but this software isn't truly an app. It's actually a RESTful(mostly) API all by itself! *Restception!*

##Requirements
 * Apache or any other similar HTTP server (If another server is used, URL rewriting and basic access control may not work)
 * PHP 7.0.0 or higher (Could be made to work with earlier versions with some smaller modifications)
 * MySQL 5.7.1 or higher

##Installation
Installing EVE Match consists of several steps:

1. Install all the required *things* and make sure they are all running properly and in harmony.
2. Fill out `data.php` with all the required constants. The two main parts you need to fill out are the database connection settings and the third party app info you get when you create an app at https://developers.eveonline.com/. You can also adjust some of the other constants, but it would probably be best if you didn't touch them.
3. Run `install.php`. Once you open it, it will create all the tables used by the app, and import some information from SDE exports(included). After the script finishes setting up the database, you should delete it to avoid reinstalling the application accidentally(Not only will you probably lose all the data, it won't even work after that). If you are reading this in the distant future and there are new systems and stargates popping up all over New Eden, I would just give up(Or get a fresh SDE export from https://www.fuzzwork.co.uk/dump/). After you are done with that, the app will be ready to receive ~~new users~~ API calls!
4. Set up automated tasks. There are two scripts in the `cron` folder which should be run periodically to make sure the location data of users is up to date. `updateActive.php` should be run relatively often(every 10 minutes or so, though you could go as far as to run it every minute) and `updateInactive.php` should be run less often than `updateActive.php`(every hour or two). On most Unix based operating systems you can do so using the [Cron](https://en.wikipedia.org/wiki/Cron) utility, but it can also be done on almost any other operating system.

##API documentation
The API is not documented at the moment. Check back later?

##Demo
If you want to try EVE Match out without installing it, you can do so at https://epa-eve.com/evematch/
There's even a nice little web interface for it so that you don't have to manually type all the HTTP requests!
If it's not available at the moment, I'm sorry. Setting up a web server is not an easy thing to do if you've never done it before.

##Licensing
This software is licensed under the [MIT License](https://en.wikipedia.org/wiki/MIT_License):

Copyright © 2016 Carbon Alabel

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
