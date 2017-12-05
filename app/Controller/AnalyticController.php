<?php

namespace Kanboard\Controller;

use Kanboard\Filter\TaskProjectFilter;
use Kanboard\Model\TaskModel;

/**
 * Project Analytic Controller
 *
 * @package  Kanboard\Controller
 * @author   Frederic Guillot
 */
class AnalyticController extends BaseController
{
    /**
     * Show average Lead and Cycle time
     *
     * @access public
     */
    public function leadAndCycleTime()
    {
        $project = $this->getProject();
        list($from, $to) = $this->getDates($project);

        $this->response->html($this->helper->layout->analytic('analytic/lead_cycle_time', array(
            'values' => array(
                'from' => $from,
                'to' => $to,
            ),
            'project' => $project,
            'average' => $this->averageLeadCycleTimeAnalytic->build($project['id']),
            'metrics' => $this->projectDailyStatsModel->getRawMetrics($project['id'], $from, $to),
            'title' => t('Lead and cycle time'),
        )));
    }

    /**
     * Show comparison between actual and estimated hours chart
     *
     * @access public
     */
    public function timeComparison()
    {
        $project = $this->getProject();

        $paginator = $this->paginator
            ->setUrl('AnalyticController', 'timeComparison', array('project_id' => $project['id']))
            ->setMax(30)
            ->setOrder(TaskModel::TABLE.'.id')
            ->setQuery($this->taskQuery
                ->withFilter(new TaskProjectFilter($project['id']))
                ->getQuery()
            )
            ->calculate();

        $this->response->html($this->helper->layout->analytic('analytic/time_comparison', array(
            'project' => $project,
            'paginator' => $paginator,
            'metrics' => $this->estimatedTimeComparisonAnalytic->build($project['id']),
            'title' => t('Estimated vs actual time'),
        )));
    }

    /**
     * Show average time spent by column
     *
     * @access public
     */
    public function averageTimeByColumn()
    {
        $project = $this->getProject();

        $this->response->html($this->helper->layout->analytic('analytic/avg_time_columns', array(
            'project' => $project,
            'metrics' => $this->averageTimeSpentColumnAnalytic->build($project['id']),
            'title' => t('Average time into each column'),
        )));
    }

    /**
     * Show tasks distribution graph
     *
     * @access public
     */
    public function taskDistribution()
    {
        $project = $this->getProject();

        $this->response->html($this->helper->layout->analytic('analytic/task_distribution', array(
            'project' => $project,
            'metrics' => $this->taskDistributionAnalytic->build($project['id']),
            'title' => t('Task distribution'),
        )));
    }

    /**
     * Show users repartition
     *
     * @access public
     */
    public function userDistribution()
    {
        $project = $this->getProject();

        $this->response->html($this->helper->layout->analytic('analytic/user_distribution', array(
            'project' => $project,
            'metrics' => $this->userDistributionAnalytic->build($project['id']),
            'title' => t('User repartition'),
        )));
    }

    /**
     * Show cumulative flow diagram
     *
     * @access public
     */
    public function cfd()
    {
        $this->commonAggregateMetrics('analytic/cfd', 'total', t('Cumulative flow diagram'));
    }

    public function cfd_readonly()
    {
        $token = $this->request->getStringParam('token');
        $project = $this->projectModel->getByToken($token);

        if (empty($project)) {
            throw AccessForbiddenException::getInstance()->withoutLayout();
        }

        $this->commonAggregateMetrics('analytic/cfd', 'total', t('Cumulative flow diagram'),$project);
    }
    /**
     * Show burndown chart
     *
     * @access public
     */
    public function burndown()
    {
        $this->commonAggregateMetrics('analytic/burndown', 'score', t('Burndown chart'));
    }

    public function burndown_readonly()
    {
        $token = $this->request->getStringParam('token');
        $project = $this->projectModel->getByToken($token);

        if (empty($project)) {
            throw AccessForbiddenException::getInstance()->withoutLayout();
        }

        $this->commonAggregateMetrics('analytic/burndown', 'score', t('Burndown chart'),$project);
    }
    /**
     * Common method for CFD and Burdown chart
     *
     * @access private
     * @param string $template
     * @param string $column
     * @param string $title
     */
    private function commonAggregateMetrics($template, $column, $title, $project = '')
    {
        if ($project == '') {
            $project = $this->getProject();
            $noLayout = false;
        } else {
            $noLayout = true;
        }

        list($from, $to, $sprintID) = $this->getDates($project);

        if ($template == 'analytic/burndown') {
            $displayGraph = True;
            $metrics = $this->projectDailyColumnStatsModel->getAggregatedMetricsBurn($project['id'], $from, $to, $column);
        } else {
            $displayGraph = $this->projectDailyColumnStatsModel->countDays($project['id'], $from, $to) >= 2;
            $metrics = $displayGraph ? $this->projectDailyColumnStatsModel->getAggregatedMetrics($project['id'], $from, $to, $column) : array();
        }

        $this->response->html($this->helper->layout->analytic($template, array(
            'values'        => array(
                'from' => $from,
                'to'   => $to,
                'sprintID'     => $sprintID,
            ),
            'display_graph' => $displayGraph,
            'metrics'       => $metrics,
            'project'       => $project,
            'title'         => $title,
            'no_layout'     => $noLayout,
        )));
    }

    private function getDates($project)
    {
        $values = $this->request->getValues();

        $baseDate = strtotime($project['start_date']);
        $curDate = strtotime(Date("Y-m-d"));
        $sprintID = -1;
        $cycle_unit = SPRINT_CYCLE_UNIT != null ? SPRINT_CYCLE_UNIT : 2;

        if (! empty($values)) {
            $from = $this->dateParser->getIsoDate($values['from']);
            $to = $this->dateParser->getIsoDate($values['to']);
            $sprintID = 0;
            if ($baseDate != "" ) {
                if ($values['sprintID'] != "" && $values['sprintID'] == 0) {
                    $from = $project['start_date'];
                    $to = Date("Y-m-d");
                } else if ($values['sprintID'] > 0) {
                    $sprintID = $values['sprintID'];
                    $from = date("Y-m-d", $baseDate + 86400 * ($cycle_unit * 7 * ($sprintID - 1)));
                    $to = date("Y-m-d", $baseDate + 86400 * ($cycle_unit * 7 * $sprintID - 1));
                }
            }
        } else {
            if ($baseDate != '') {
                $days = floor(($curDate - $baseDate)/86400);
                $sprintID = floor($days / ($cycle_unit * 7)) + 1;
                $from = date("Y-m-d",$baseDate + 86400 * ($cycle_unit * 7 * ($sprintID - 1)));
                $to =  date("Y-m-d",$baseDate + 86400 * ($cycle_unit * 7 * $sprintID - 1));
            } else {
                $from = $this->request->getStringParam('from', date('Y-m-d', strtotime('-1week')));
                $to = $this->request->getStringParam('to', date('Y-m-d'));
            }
        }

        return array($from, $to, $sprintID);
    }
}
