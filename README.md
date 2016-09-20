# kontra

This is one of the first PHP projects written back in 2003 and then slightly enhanced in 2008.

I kept the original formatting of files, just converted `cp1251` codepage, I was using back then, into `utf8`.

# What is this?

This is a piece of code that worked since year 2003.

It was used by a group of 10-15 people to create jokes online. Jokes were a part of a bigger thing, called КВН (famous game from USSR and long after).

The name *kontra* comes from the russian *kontrolnaja*, which stands for *test*.
This format was pretty popular offline, where people would write down random questions, and the other people would try to reply in a funny and humorous way to it.
After all questions were answered, one would read it aloud, one-by-one, and depending on the reaction of everyone (funny or not), the joke might be promoted, to be used later in a game.

So this project was simply covering the writing part. Where anyone (with a proper password) could write questions and answers, vote and make fun ;)


# Why the code is so .. funny?

Well, it was written when PHP4 was becoming more and more popular.
It used MySQL 3 and then 4  database.

It also introduced self-written template parser (see [mytempl](mytempl.php)), which boasted some nice features like includes, repeated blocks, conditionals and much much less!

It used cool-feature for gzipping the output (see [gzdoc](gzdoc.php))

And, it was a *single page app*, because everything was inside [kontra.php](kontra.php) :D

Coming from Perl world, led to some parts of code to look like it is perl


```
    \@my_mysql_query("update kanswer set rating=rating+$rate where aid='$aid'") or die(mysql_error());
```

Yep, no regrets for errors & warnings


```
    error_reporting(0); 
    $res2=\@my_mysql_query("select aid from kanswer where aquestion='$qid'");
```

Although it looked like you can do SQL-injections quite easily, it was. Not everything was properly escaped, but I was trusting my men :D


# Ok, why should it be public then?

Well, it might not be the most perfect piece of code written, but it was delivering the business value. For quite some years.
It was also a pretty successful back then (judging by the span of time it was used through and amount of people that used it).

Plus it looks funny, I'm not going to feel any shame over this code, it was written more than 13 years ago.


# Sounds familiar?

The idea was improved, redesigned, redeveloped and is now available as [loliful app](https://loliful.io/)



# License

Do whatever you want license. Especially if you'd make it start in the first place ;)


# How do i run it? 

Well, if you really want that, you can use [docker](https://www.docker.com)

```
   # build php5 mysql container with mysql modules
   docker build -t "kontra_php5_apache" .

   # add custom vhost using ip of your docker machine 
   sudo echo '127.0.0.1 mf.local.net' >> /etc/hosts

   # start with compose
   docker-compose up -d

   # import into mysql container kontra-db-schema.sql
```

With some luck it should be accessible through `http://mf.local.net` in your browser.