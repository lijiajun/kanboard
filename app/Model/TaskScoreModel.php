<?php

namespace Kanboard\Model;

use Kanboard\Core\Base;
use Kanboard\Core\Security\Role;

/**
 * Class TaskScoreModel
 *
 * @package Kanboard\Model
 * @author  Frederic Guillot
 */
class TaskScoreModel extends Base
{
    /**
     * SQL table name
     *
     * @var string
     */
    const TABLE = 'task_has_scores';

    const EVENT_EVALUATE = 'task.evaluate';
    const EVENT_REVALUATE = 'task.revaluate';
    const EVENT_EVALUATE_RESULT = 'task.evaluate.result';

    public function shouldReceiveNotification(array $user, array $task)
    {
        if (NON_EVAULATE_MEMBER != null) {
            $noNotifyUsers = explode(',',NON_EVAULATE_MEMBER);
            foreach($noNotifyUsers as $user_id){
                if ($user['id'] == $user_id)
                    return false;
            }
        }

        if ($user['sub_role'] != $task['sub_role'])
            return false;

        if ($this->getRecordbyUserTask($user['id'],$task['id']) != null)
            return false;

        if ($user['id'] == $task['owner_id'] and $task['score'] > 0)
            return false;

        return true;
    }

    public function insertTaskScore(array $curUser, array $task_scores, $project_id)
    {
        $allUsers = array();
        $members = $this->getProjectUserMembers($project_id,$curUser['sub_role']);
        $groups = $this->getProjectGroupMembers($project_id,$curUser['sub_role']);
        foreach (array_merge($members, $groups) as $user) {
            if (! isset($allUsers[$user['id']])) {
                $allUsers[$user['id']] = $user;
            }
        }
        $evaCount = 0;
        $this->db->startTransaction();
        foreach ($task_scores as $task_id => $score) {
            if ($score == null)
                continue;
            $this->db->table(self::TABLE)->eq('task_id', $task_id)->eq('user_id', $curUser['id'])->remove();
            $result = $this->db->table(self::TABLE)->insert(array(
                'task_id' => $task_id,
                'user_id' => $curUser['id'],
                'score' => $score,
                'is_done' => 0,
            ));
            if (!$result) {
                $this->db->cancelTransaction();
                return $evaCount;
            }

            $evaCount++;
            $evaScore = $this->getEvaScore($task_id,$curUser,count($allUsers));
            if ($evaScore > 0) {
                $result = $this->taskModel->updateTaskScore($task_id,$evaScore);
                if (!$result) {
                    $this->db->cancelTransaction();
                    return $evaCount;
                }
                $this->updateScoreState($task_id);
                //notify task owner
                $this->sendEvaTaskResult($task_id);
            }
        }
        $this->db->closeTransaction();

        return $evaCount;
    }

    private function getEvaScore($task_id,array $curUser, $totalUserNum)
    {
        $FinalScore = 0;
        $OwnerId = 0;
        $OwnerScore = $this->taskModel->getOwnerScore($task_id);
        $EvaUsers = $this->getEvaUsersbyTask($task_id,$curUser['sub_role']);

        foreach ($OwnerScore as $user_id => $score) {
            if ($score != "0" and !isset($EvaUsers[$user_id]))
                $EvaUsers[$user_id] = $score;
            $OwnerId = $user_id;
        }

        if (count($EvaUsers) != $totalUserNum)
            return $FinalScore;

        $RefScores = array("5","10","20","30","50","80","130","200");
        $EvaScores = array();
        foreach ($EvaUsers as $user_id => $score) {
            $EvaScores[] = $score;
        }
        sort($EvaScores);
        if (count($EvaScores) > 3) {
            $AveScore = 0;
            for($i = 1; $i < count($EvaScores) - 1; ++$i){
                $AveScore += $EvaScores[$i];
            }
            $AveScore = $AveScore / (count($EvaScores) - 2);
            for($i = 0; $i < count($RefScores); ++$i){
                if ($AveScore == $RefScores[$i]) {
                    $FinalScore = $AveScore;
                    break;
                } elseif ($AveScore < $RefScores[$i]) {
                    if ($AveScore < $EvaUsers[$OwnerId]) {
                        $FinalScore = $RefScores[$i];
                    } else {
                        $FinalScore = $RefScores[$i - 1];
                    }
                    break;
                }
            }
        } elseif (count($EvaScores) == 1) {
            $FinalScore = $EvaScores[0];
        } elseif (count($EvaScores) == 2) {
            if (isset($EvaUsers[$OwnerId])) {
                $FinalScore = $EvaUsers[$OwnerId];
            } else {
                $FinalScore = $EvaScores[0];
            }
        } elseif (count($EvaScores) == 3) {
            $FinalScore = $EvaScores[1];
        }

        return $FinalScore;
    }

    public function getEvaUsersbyTask($task_id,$sub_role)
    {
        $result = $this->db->table(self::TABLE)
            ->columns('user_id','score')
            ->join(UserModel::TABLE, 'id', 'user_id')
            ->eq('task_id', $task_id)
            ->eq(UserModel::TABLE.'.sub_role', $sub_role)
            ->findAll();

        return array_column($result, 'score','user_id');
    }

    public function getRecordbyUserTask($user_id,$task_id)
    {
        $record = $this->db->table(self::TABLE)
            ->columns('user_id','task_id')
            ->eq('user_id', $user_id)
            ->eq('task_id', $task_id)
            ->findAll();

        return array_column($record, 'user_id','task_id');
    }

    public function getUserScorebyTask($task_id)
    {
        $record = $this->db->table(self::TABLE)
            ->columns('user_id','score')
            ->eq('task_id', $task_id)
            ->findAll();

        return array_column($record, 'score','user_id');
    }

    private function getProjectUserMembers($project_id, $sub_role)
    {
        return $this->db
            ->table(ProjectUserRoleModel::TABLE)
            ->columns(UserModel::TABLE.'.id', UserModel::TABLE.'.username', UserModel::TABLE.'.name', UserModel::TABLE.'.sub_role')
            ->join(UserModel::TABLE, 'id', 'user_id')
            ->eq(ProjectUserRoleModel::TABLE.'.project_id', $project_id)
            ->eq(UserModel::TABLE.'.is_active', 1)
            ->eq(UserModel::TABLE.'.sub_role', $sub_role)
            ->neq(ProjectUserRoleModel::TABLE.'.role', Role::PROJECT_VIEWER)
            ->neq(ProjectUserRoleModel::TABLE.'.role', Role::PROJECT_EXT_MEMBER)
            ->findAll();
    }

    private function getProjectGroupMembers($project_id, $sub_role)
    {
        return $this->db
            ->table(ProjectGroupRoleModel::TABLE)
            ->columns(UserModel::TABLE.'.id', UserModel::TABLE.'.username', UserModel::TABLE.'.name', UserModel::TABLE.'.sub_role')
            ->join(GroupMemberModel::TABLE, 'group_id', 'group_id', ProjectGroupRoleModel::TABLE)
            ->join(UserModel::TABLE, 'id', 'user_id', GroupMemberModel::TABLE)
            ->eq(ProjectGroupRoleModel::TABLE.'.project_id', $project_id)
            ->eq(UserModel::TABLE.'.is_active', 1)
            ->eq(UserModel::TABLE.'.sub_role', $sub_role)
            ->neq(ProjectGroupRoleModel::TABLE.'.role', Role::PROJECT_VIEWER)
            ->neq(ProjectGroupRoleModel::TABLE.'.role', Role::PROJECT_EXT_MEMBER)
            ->findAll();
    }

    private function updateScoreState($task_id)
    {
        $result = $this->db->table(self::TABLE)->eq('task_id', $task_id)->update(array(
            'is_done' => 1,
        ));

        if (! $result) {
            $this->db->cancelTransaction();
            return false;
        }

        return true;
    }

    private function sendEvaTaskResult($task_id)
    {
        if ($task_id > 0) {
            $tasks = $this->taskFinderModel->getEvaTaskbyId($task_id);
        }
        else {
            return false;
        }
        if (!empty($tasks)) {
            $task = $tasks[0];
            $users = $this->userModel->getUsertoNotification($task['owner_id']);
        }
        else {
            return false;
        }

        $this->userNotificationModel->sendUserNotification(
            $users[0],
            TaskScoreModel::EVENT_EVALUATE_RESULT,
            array('task' => $task));

        return true;
    }

    public function clearTaskScore($task_id)
    {
        $this->db->startTransaction();
        $this->db->table(self::TABLE)->eq('task_id', $task_id)->remove();
        $this->taskModel->updateTaskScore($task_id,0);
        $this->db->closeTransaction();

        return true;
    }
}

