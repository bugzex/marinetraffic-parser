# MarineTrafficParser
The data-crawler for collecting data of ships movements from `marinetraffic.com` website.

## Installation

### Copy the project files and install the dependencies.
Run commands from your terminal (console):
```
git clone https://github.com/bugzex/marinetraffic-parser.git
```

```
cd marinetraffic-parser
```

```
composer install
```

### Import the example database and configure the connection to it.
- Create `marines` database in your MySQL 5 server.
- Execute `db.sql` SQL-file from the root directory of this project.
- Default connection settings for this application are: user - `root`, password - `empty`, host - `localhost`.
- If you need change any database connection settings, then see the configuration file: `/config/db.php`. 

## How to run the crawler
Run the command from your terminal (console): `yii download`.
