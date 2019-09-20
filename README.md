# Ulole ORM
## A PHP ORM

Features:
- Multi Database support
- Supports many SQL Server (MySQL, Postgre)
- Migration System

#### env.json
You need a env.json for the Ulole ORM
```json
{
    "databases": {
        "main": {
            "driver": "mysql",
            "username": "name",
            "password": "password",
            "database": "database",
            "server": "localhost",
            "port": 3306
        }
    }
}
```