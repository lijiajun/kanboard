#!/bin/bash

source /home/webs/kanban.aivb.pub/tools/config

#curr_date=`date -d "yesterday" "+%Y%m%d_%H%M%S"` #昨天
curr_date=`date "+%Y%m%d"` #今天
file_name="kanban_${project_name_1}_$curr_date.jpg"
xvfb-run CutyCapt --min-width=1366 --min-height=768 --url=$project_url_1 --out=$file_path/$file_name

