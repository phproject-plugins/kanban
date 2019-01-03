<?php

namespace Plugin\Kanban;

use Helper\View;
use Model\Issue;
use Model\Issue\Backlog;
use Model\Issue\Detail as IssueDetail;
use Model\Issue\Status;
use Model\Issue\Type;
use Model\Sprint;
use Model\User;
use Model\User\Group;

class Controller extends \Controller
{
    public function __construct()
    {
        $this->_userId = $this->_requireLogin();
    }

    /**
     * View a Kanban board
     *
     * Accepts parameters: group, sprint
     *
     * @param \Base $f3
     * @param array $params
     */
    public function index(\Base $f3, array $params)
    {
        $group = new User;
        $group->load($params['group']);
        if (!$group->id) {
            $f3->error(404);
            return;
        }

        $sprint = new Sprint;
        if (empty($params['sprint']) || !intval($params['sprint'])) {
            $localDate = date('Y-m-d', View::instance()->utc2local());
            $sprint->load(['? BETWEEN start_date AND end_date', $localDate]);
        } else {
            $sprint = $sprint->load($params['sprint']);
        }
        if (!$sprint->id) {
            $f3->error(404);
            return;
        }

        $f3->set('title', 'Kanban Board');
        $f3->set('group', $group->id);
        $f3->set('sprint', $sprint->id);
        $this->_render("kanban/view/index.html");
    }

    /**
     * Get swimlanes for the Kanban board
     * @param \Base $f3
     */
    public function boardLanes(\Base $f3)
    {
        $statusModel = new Status;
        $statuses = $statusModel->find('taskboard > 0', ['order' => 'taskboard_sort ASC']);
        $return = [];
        foreach ($statuses as $status) {
            $return[] = $status->cast();
        }
        $this->_printJson($return);
    }

    /**
     * Get items on the Kanban board
     * @param \Base $f3
     */
    public function boardData(\Base $f3)
    {
        $groupId = $f3->get('GET.group');
        if (!$groupId) {
            $f3->error(400, 'A group ID is required to load board data.');
        }
        $sprintId = $f3->get('GET.sprint');
        if (!intval($sprintId)) {
            $f3->error(400, 'A sprint ID is required to load board data.');
        }

        // Add owner/sprint filters
        $user = new User;
        $user->load($groupId);
        if ($user->role == 'group') {
            $groupModel = new Group;
            $groupUsers = $groupModel->find(['group_id = ?', $user->id]);
            $filterUsers = [intval($groupId)];
            foreach ($groupUsers as $u) {
                $filterUsers[] = $u['user_id'];
            }
        } else {
            $filterUsers = [intval($groupId)];
        }
        $ownerStr = implode(',', $filterUsers);
        $filter = "owner_id IN ($ownerStr)";
        $filter .=  ' AND sprint_id = ' . intval($sprintId);

        // Add status filter
        $statusModel = new Status;
        $statuses = $statusModel->find('taskboard > 0', ['order' => 'taskboard_sort ASC']);
        $statusIds = [];
        foreach ($statuses as $status) {
            $statusIds[] = $status->id;
        }
        $statusStr = implode(',', $statusIds);
        $filter .= " AND status IN ($statusStr)";

        // Add type filter
        $type = new Type;
        $types = $type->find(['role = ?', 'project']);
        $typeIds = [];
        foreach ($types as $type) {
            $typeIds[] = $type->id;
        }
        $typeStr = implode(',', $typeIds);
        $filter .= " AND type_id IN ($typeStr)";

        // Find issues
        $issueModel = new IssueDetail;
        $backlogModel = new Backlog;
        $backlog = $backlogModel->load(['sprint_id' => intval($sprintId)]);
        if ($backlog->issues) {
            $backlogIds = "'" . implode("','", explode(',', trim($backlog->issues, '[]'))) . "'" ?: "'0'";
        }
        $issues = $issueModel->find($filter, $backlog->issues ? ['order' => "FIELD(id, $backlogIds), id"] : null);
        $return = [];
        foreach ($issues as $issue) {
            $return[] = [
                'id' => $issue->id,
                'name' => $issue->name,
                'status' => $issue->status,
                'owner_id' => $issue->owner_id,
                // TODO: get owner metadata from client-side list using computed props on kanban-issue
                'owner_name' => $issue->owner_name,
                'owner_task_color' => $issue->owner_task_color,
            ];
        }

        $this->_printJson($return);
    }

    /**
     * Handle moving issues on the board
     *
     * @param \Base $f3
     * @param array $params
     */
    public function move(\Base $f3, array $params)
    {
        $issue = new Issue;
        $issue->load($params['id']);
        if (!$issue->id) {
            $f3->error(404);
            return;
        }

        $issue->status = $f3->get('POST.status');
        $issue->save();

        return $this->_printJson($issue->cast());
    }
}
