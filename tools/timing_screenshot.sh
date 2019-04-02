#!/bin/bash

#curr_date=`date -d "yesterday" "+%Y%m%d_%H%M%S"` #昨天
curr_date=`date "+%Y%m%d"` #今天

if [ -z "$1" ]; then
    echo "请输入项目编号"
    exit 1
fi

token=`mysql -uroot -Dkanboard -pmysql -e"select token from projects where id = $1;" | sed -n '2p'`
if [ -z "$token" ]; then
    echo "项目编号$1对应的项目不存在或未公开，请输入其他项目编号。"
    exit 1
fi

project_name=`mysql -uroot -Dkanboard -pmysql -e"select name from projects where id = $1;" | sed -n '2p'`
if [ -z "$project_name" ]; then
    echo "项目编号$1对应的项目不存在，请输入其他项目编号。"
    exit 1
fi

file_path='/home/webs/kanban.aivb.pub/tools/Screenshots'
kanban_file_name="${project_name}_kanboard_$curr_date.jpg"
#burntask_file_name="${project_name}_kanboard_${curr_date}_burntask.jpg"
#burnscore_file_name="${project_name}_kanboard_${curr_date}_burnscore.jpg"
project_url="http://kanban.aivb.pub/?controller=BoardViewController&action=readonly&token=$token"
#project_burntasks_url="http://kanban.aivb.pub/?controller=AnalyticController&action=burndown_tasks_readonly&token=$token"
#project_burnscore_url="http://kanban.aivb.pub/?controller=AnalyticController&action=burndown_score_readonly&token=$token"

echo $kanban_file_name
#echo $burntask_file_name
#echo $burnscore_file_name
echo $project_url
#echo $project_burntasks_url
#echo $project_burnscore_url
xvfb-run CutyCapt --min-width=1366 --min-height=768 --url=$project_url --out=$file_path/$kanban_file_name
#sleep 3
#xvfb-run CutyCapt --min-width=1366 --min-height=400 --url=$project_burntasks_url --out=$file_path/$burntask_file_name --delay=5000
#sleep 3
#xvfb-run CutyCapt --min-width=1366 --min-height=400 --url=$project_burnscore_url --out=$file_path/$burnscore_file_name --delay=5000

