<?php

namespace Kanboard\Model;

use Kanboard\Core\Base;

/**
 * Project Daily Column Stats
 *
 * @package  Kanboard\Model
 * @author   Frederic Guillot
 */
class ProjectDailyColumnStatsModel extends Base
{
    /**
     * SQL table name
     *
     * @var string
     */
    const TABLE = 'project_daily_column_stats';

    /**
     * Update daily totals for the project and for each column
     *
     * "total" is the number open of tasks in the column
     * "score" is the sum of tasks score in the column
     *
     * @access public
     * @param  integer    $project_id    Project id
     * @param  string     $date          Record date (YYYY-MM-DD)
     * @return boolean
     */
    public function updateTotals($project_id, $date)
    {
        $this->db->startTransaction();
        $this->db->table(self::TABLE)->eq('project_id', $project_id)->eq('day', $date)->remove();

        foreach ($this->getStatsByColumns($project_id) as $column_id => $column) {
            $this->db->table(self::TABLE)->insert(array(
                'day' => $date,
                'project_id' => $project_id,
                'column_id' => $column_id,
                'total' => $column['total'],
                'score' => $column['score'],
                'remain_total' => $column['remain_total'],
                'remain_score' => $column['remain_score'],
            ));
        }

        $this->db->closeTransaction();

        return true;
    }

    /**
     * Count the number of recorded days for the data range
     *
     * @access public
     * @param  integer    $project_id    Project id
     * @param  string     $from          Start date (ISO format YYYY-MM-DD)
     * @param  string     $to            End date
     * @return integer
     */
    public function countDays($project_id, $from, $to)
    {
        return $this->db->table(self::TABLE)
            ->eq('project_id', $project_id)
            ->gte('day', $from)
            ->lte('day', $to)
            ->findOneColumn('COUNT(DISTINCT day)');
    }

    /**
     * Get aggregated metrics for the project within a data range
     *
     * [
     *    ['Date', 'Column1', 'Column2'],
     *    ['2014-11-16', 2, 5],
     *    ['2014-11-17', 20, 15],
     * ]
     *
     * @access public
     * @param  integer    $project_id    Project id
     * @param  string     $from          Start date (ISO format YYYY-MM-DD)
     * @param  string     $to            End date
     * @param  string     $field         Column to aggregate
     * @return array
     */
    public function getAggregatedMetrics($project_id, $from, $to, $field = 'total')
    {
        $columns = $this->columnModel->getList($project_id);
        $metrics = $this->getMetrics($project_id, $from, $to);
        return $this->buildAggregate($metrics, $columns, $field);
    }

    /**
     * Build aggregate
     *
     * @access private
     * @param  array   $metrics
     * @param  array   $columns
     * @param  string  $field
     * @return array
     */
    private function buildAggregate(array &$metrics, array &$columns, $field)
    {
        $column_ids = array_keys($columns);
        $days = array_unique(array_column($metrics, 'day'));
        $rows = array(array_merge(array(e('Date')), array_values($columns)));

        foreach ($days as $day) {
            $rows[] = $this->buildRowAggregate($metrics, $column_ids, $day, $field);
        }

        return $rows;
    }

    /**
     * Build one row of the aggregate
     *
     * @access private
     * @param  array   $metrics
     * @param  array   $column_ids
     * @param  string  $day
     * @param  string  $field
     * @return array
     */
    private function buildRowAggregate(array &$metrics, array &$column_ids, $day, $field)
    {
        $row = array($day);

        foreach ($column_ids as $column_id) {
            $row[] = $this->findValueInMetrics($metrics, $day, $column_id, $field);
        }

        return $row;
    }

    /**
     * Get aggregated metrics for the project within a data range
     *
     * [
     *    ['Date', 'plan', 'real'],
     *    ['2014-11-16', 2, 5],
     *    ['2014-11-17', 20, 15],
     * ]
     *
     * @access public
     * @param  integer    $project_id    Project id
     * @param  string     $from          Start date (ISO format YYYY-MM-DD)
     * @param  string     $to            End date
     * @param  string     $field         Column to aggregate
     * @return array
     */
    public function getAggregatedMetricsBurn($project_id, $from, $to, $field = 'total')
    {
        $columns = $this->columnModel->getListWithoutCompleteCol($project_id);
        $metrics = $this->getMetrics($project_id, $from, $to);
        return $this->buildAggregateBurn($metrics, $columns, $field, $from, $to);
    }

    /**
     * Fetch metrics
     *
     * @access public
     * @param  integer    $project_id    Project id
     * @param  string     $from          Start date (ISO format YYYY-MM-DD)
     * @param  string     $to            End date
     * @return array
     */
    public function getMetrics($project_id, $from, $to)
    {
        return $this->db->table(self::TABLE)
            ->eq('project_id', $project_id)
            ->gte('day', $from)
            ->lte('day', $to)
            ->asc(self::TABLE.'.day')
            ->findAll();
    }

    /**
     * Build aggregate
     *
     * @access private
     * @param  array   $metrics
     * @param  array   $columns
     * @param  string  $field
     * @return array
     */
    private function buildAggregateBurn(array &$metrics, array &$columns, $field, $from, $to)
    {
        $column_ids = array_keys($columns);
        $rows = array(array_merge(array(e('Date')), array(e('Ideal curve')), array(e('Plan tasks')), array(e('Unplan tasks'))));

        $days = (strtotime($to) - strtotime($from)) / 86400 + 1;
        for($i=0; $i < $days; $i++){
            $rows[] = $this->buildRowAggregateBurn($metrics, $column_ids, $from, $i, $days, $field);
        }

        return $rows;
    }

    /**
     * Build one row of the aggregate
     *
     * @access private
     * @param  array   $metrics
     * @param  array   $column_ids
     * @param  string  $date
     * @param  string  $field
     * @return array
     */
    private function buildRowAggregateBurn(array &$metrics, array &$column_ids, $from, $day_index, $days, $field)
    {
        static $plan_remain = 0;
        static $unplan_remain = 0;
        $plan_tmp = 0;
        $unplan_tmp = 0;
        $ideal_remain = 0;
        $date = date('Y-m-d', strtotime($from) + (86400 * $day_index));
        $row = array($date);

        foreach ($column_ids as $column_id) {
            $ideal_remain += $this->findValueInMetrics($metrics, $from, $column_id, $field);
        }
        $ideal_remain = $ideal_remain - (float) ($ideal_remain * $day_index)/ ($days - 1);
        $row[] = round($ideal_remain, 2);

        if (strtotime(Date("Y-m-d")) >= strtotime($date)) {
            $isNullDate = True;
            foreach ($metrics as $metric) {
                if ($metric['day'] === $date) {
                    $isNullDate = False;
                    break;
                }
            }

            if ($isNullDate) {
                $row[] = $plan_remain;
                $row[] = $unplan_remain;
            } else {
                foreach ($column_ids as $column_id) {
                    $plan_tmp += $this->findValueInMetrics($metrics, $date, $column_id, $field);
                    $unplan_tmp += $this->findValueInMetrics($metrics, $date, $column_id, 'remain_'.$field);
                }

                $row[] = $plan_tmp;
                $plan_remain = $plan_tmp;

                $row[] = $unplan_tmp;
                $unplan_remain = $unplan_tmp;
            }
        }

        return $row;
    }

    /**
     * Find the value in the metrics
     *
     * @access private
     * @param  array   $metrics
     * @param  string  $day
     * @param  string  $column_id
     * @param  string  $field
     * @return integer
     */
    private function findValueInMetrics(array &$metrics, $day, $column_id, $field)
    {
        foreach ($metrics as $metric) {
            if ($metric['day'] === $day && $metric['column_id'] == $column_id) {
                if ($field == 'score' || $field == 'remain_score') {
                    return (float) $metric[$field]/10;
                } else {
                    return (int) $metric[$field];
                }
            }
        }

        return 0;
    }

    /**
     * Get number of tasks and score by columns
     *
     * @access private
     * @param  integer $project_id
     * @return array
     */
    private function getStatsByColumns($project_id)
    {
        $project = $this->projectModel->getById($project_id);
        $totals = $this->getTotalByColumns($project_id, $project['burn_tags']);
        $scores = $this->getScoreByColumns($project_id, $project['burn_tags']);
        $remain_totals = $this->getRemainTotalByColumns($project_id, $project['burn_tags']);
        $remain_scores = $this->getRemainScoreByColumns($project_id, $project['burn_tags']);
        $column_ids = $this->columnModel->getIdList($project_id);

        $columns = array();
        foreach ($column_ids as $key => $value) {
            $col_id = $value['id'];
            $total = isset($totals[$col_id]) ? $totals[$col_id] : "0";
            $score = isset($scores[$col_id]) ? $scores[$col_id] : "0";
            $remain_total = isset($remain_totals[$col_id]) ? $remain_totals[$col_id] : "0";
            $remain_score = isset($remain_scores[$col_id]) ? $remain_scores[$col_id] : "0";
            if ($total == "0" and $score == "0" and $remain_total == "0" and $remain_score == "0")
                continue;
            $columns[$col_id] = array('total' => $total, 'score' => $score, 'remain_total' => $remain_total, 'remain_score' => $remain_score);
        }

        return $columns;
    }

    /**
     * Get number of tasks and score by columns
     *
     * @access private
     * @param  integer $project_id
     * @return array
     */
    private function getScoreByColumns($project_id, $burn_tags)
    {
        if ($burn_tags == null) {
            $stats = $this->db->table(TaskModel::TABLE)
                ->columns('column_id', 'SUM(score) AS score')
                ->eq('project_id', $project_id)
                ->eq('is_active', TaskModel::STATUS_OPEN)
                ->notNull('score')
                ->groupBy('column_id')
                ->findAll();
        } else {
            $stats = $this->db->table(TaskModel::TABLE)
                ->columns('column_id', 'SUM(score) AS score')
                ->eq('project_id', $project_id)
                ->eq('is_active', TaskModel::STATUS_OPEN)
                ->notNull('score')
                ->in(TaskTagModel::TABLE.'.tag_id',explode(',',$burn_tags))
                ->join(TaskTagModel::TABLE, 'task_id', 'id')
                ->groupBy('column_id')
                ->findAll();
        }


        return array_column($stats, 'score', 'column_id');
    }

    private function getRemainScoreByColumns($project_id, $burn_tags)
    {
        if ($burn_tags == null) {
            $stats = $this->db->table(TaskModel::TABLE)
                ->columns('column_id', 'SUM(score) AS score')
                ->eq('project_id', $project_id)
                ->eq('is_active', TaskModel::STATUS_OPEN)
                ->notNull('score')
                ->groupBy('column_id')
                ->findAll();
        } else {
            $subquery = $this->db->table(TaskTagModel::TABLE)
                ->columns('task_id')->in(TaskTagModel::TABLE.'.tag_id',explode(',',$burn_tags));
            $stats = $this->db->table(TaskModel::TABLE)
                ->columns('column_id', 'SUM(score) AS score')
                ->eq('project_id', $project_id)
                ->eq('is_active', TaskModel::STATUS_OPEN)
                ->notNull('score')
                ->notInSubquery(TaskModel::TABLE.'.id',$subquery)
                ->groupBy('column_id')
                ->findAll();
        }


        return array_column($stats, 'score', 'column_id');
    }

    /**
     * Get number of tasks and score by columns
     *
     * @access private
     * @param  integer $project_id
     * @return array
     */
    private function getTotalByColumns($project_id, $burn_tags)
    {
        if ($burn_tags == null) {
            $stats = $this->db->table(TaskModel::TABLE)
                ->columns('column_id', 'COUNT(*) AS total')
                ->eq('project_id', $project_id)
                ->in('is_active', $this->getTaskStatusConfig())
                ->groupBy('column_id')
                ->findAll();
        } else {
            $stats = $this->db->table(TaskModel::TABLE)
                ->columns('column_id', 'COUNT(*) AS total')
                ->eq('project_id', $project_id)
                ->in('is_active', $this->getTaskStatusConfig())
                ->in(TaskTagModel::TABLE.'.tag_id',explode(',',$burn_tags))
                ->join(TaskTagModel::TABLE, 'task_id', 'id')
                ->groupBy('column_id')
                ->findAll();
        }

        return array_column($stats, 'total', 'column_id');
    }

    private function getRemainTotalByColumns($project_id, $burn_tags)
    {
        if ($burn_tags == null) {
            $stats = $this->db->table(TaskModel::TABLE)
                ->columns('column_id', 'COUNT(*) AS total')
                ->eq('project_id', $project_id)
                ->in('is_active', $this->getTaskStatusConfig())
                ->groupBy('column_id')
                ->findAll();
        } else {
            $subquery = $this->db->table(TaskTagModel::TABLE)
                ->columns('task_id')->in(TaskTagModel::TABLE.'.tag_id',explode(',',$burn_tags));
            $stats = $this->db->table(TaskModel::TABLE)
                ->columns('column_id', 'COUNT(*) AS total')
                ->eq('project_id', $project_id)
                ->in('is_active', $this->getTaskStatusConfig())
                ->notInSubquery(TaskModel::TABLE.'.id',$subquery)
                ->groupBy('column_id')
                ->findAll();
        }

        return array_column($stats, 'total', 'column_id');
    }

    /**
     * Get task status to use for total calculation
     *
     * @access private
     * @return array
     */
    private function getTaskStatusConfig()
    {
        if ($this->configModel->get('cfd_include_closed_tasks') == 1) {
            return array(TaskModel::STATUS_OPEN, TaskModel::STATUS_CLOSED);
        }

        return array(TaskModel::STATUS_OPEN);
    }
}
