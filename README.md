# Snekabel Server Status Page
Shows a status page with network statistics and raid-info on the server hosting it.
Make sure vnstati is installed on the server, that www-data can access stats-update.sh and that www-data has the cron jobs:
```
* * * * * /var/www/status/stats/stats-update.sh hour
* * * * * /var/www/status/stats/stats-update.sh day
* * * * * /var/www/status/stats/stats-update.sh month
```
Edit the crontab using:
```
sudo crontab -u www-data -e
```
