# OpenCart

OpenCart is a ecommerce platform for online merchants. OpenCart provides a professional and reliable foundation from which to build a successful online store.

This development branch is originated from [OpenCart master branch](https://github.com/opencart/opencart/).

## Features
* InnoDB: ```on delete``` and ```on update```
* Foreign Key: ```user_id```
* Additional MySQL database indexes
* Integration with 1c based on [Exchange1c](https://github.com/KirilLoveVE/opencart2-exchange1c/commit/39a8106f39f2abfd0a88ddf98df0864ecec092e0)
* More themes
* Full Russian localisation
* Yandex Metrika Analytics

## TODO
* Multi-user stores using global permissions: ```$this->user->hasPermission('modify', 'setting/setting')```
* Fix CORS header ```Access-Control-Allow-Origin``` missing for shop admin page of non-primary domains
* Implement per-user extensions
* Switch to per-user dashboard
* Allow layout sharing via store route

## Reporting a bug and security issues

Read the instructions below before you create a bug report.

 1. Use [Google](http://www.google.com) to search for your issue.
 2. Search the [OpenCart forum](http://forum.opencart.com/viewforum.php?f=191), ask the community if they have seen the bug or know how to fix it.
 3. Check all open and closed issues on the [GitHub bug tracker](https://github.com/opencart/opencart/issues).
 4. If your bug is related to the OpenCart core code then please create a bug report on GitHub.
 5. Make sure that your bug/issue is not related to this development branch modifications.

## How to contribute

1. Fork the repository, edit and submit a pull request.
2. Your code standards should match the [OpenCart coding standards](https://github.com/opencart/opencart/wiki/Coding-standards).

## How to install

Please read the installation instructions included in the [official repository](https://github.com/opencart/opencart/blob/master/install.txt).

## License

Creative Commons [Attribution-NoDerivatives 4.0 International](https://creativecommons.org/licenses/by-nd/4.0/legalcode).

## Links

- [Original OpenCart demo](https://www.opencart.com/index.php?route=cms/demo)
- [How to documents](http://docs.opencart.com/)
- [OpenCart forums](http://forum.opencart.com/)
- [OpenCart blog](http://www.opencart.com/index.php?route=feature/blog)
- [Newsletter](http://newsletter.opencart.com/h/r/B660EBBE4980C85C)
- [User Voice suggestions](http://opencart.uservoice.com)
