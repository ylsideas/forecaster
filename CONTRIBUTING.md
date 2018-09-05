# Contributions

Contributions are welcome and encouraged. All potential changes should be
submitted via Pull Requests on this project, on Github.

PRs will be reviewed for:
* PSR-2 Coding Standard. Please make sure all code is valid PSR-2 code.
* If there's no tests it will likely not be approved. Please add sensible
tests to the tests direction and ensure they work with the PHPUnit version
set via the dev requirements of this package. Please name all test case classes
as FooTest and have all test functions start with test. All test function
names should be in `snake_case`.
* Provide documented examples. Most the time this can simply be in the
README.md file.
* Features that break expected behaviour won't be merged in unless it's
a new major version is created.
* Keep your PRs clean and tidy. Keep your PRs to only one feature per request
with the respective documentation and tests. Try to keep all your PR branch's
history to readable and obvious commits.
* Please do not commit your phpunit.xml or composer.lock files to the repository
they have been deliberately been excluded. Make sure your PRs also do not
include any files your IDE may have included like `.idea` for people who
use PHPStorm.

And finally, thank you for any and all time you put into making this project
better. Even if your PRs are not accepted, we genuinely appreciate the time 
and effort you put in to your work!