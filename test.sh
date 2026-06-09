#!/bin/bash
timeout 10 php artisan tinker --execute="echo 'Connected'; DB::table('users')->count();" || echo "TIMEOUT OR ERROR"
