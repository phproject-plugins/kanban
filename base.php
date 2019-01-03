<?php
/**
 * @package  Kanban
 * @author   Alan Hardman <alan@phproject.org>
 * @version  0.1.0
 * @requires 1.7.5
 */

namespace Plugin\Kanban;

class Base extends \Plugin
{
    /**
     * Initialize the plugin
     * @todo load configuration and initialize socket connection
     */
    public function _load() {
        $f3 = \Base::instance();

        // Set up routes
        $f3->route("GET /kanban/@sprint/@group", "Plugin\Kanban\Controller->index");
        $f3->route("GET /kanban/boardLanes", "Plugin\Kanban\Controller->boardLanes");
        $f3->route("GET /kanban/boardData", "Plugin\Kanban\Controller->boardData");
        $f3->route("POST /kanban/move/@id", "Plugin\Kanban\Controller->move");

        // Inject JS into backlog
        $this->_addJs("
<script>
$('.dropdown-menu a').click(function(e) {
    setTimeout(function() {
        let groupId = $('.dropdown-menu .active a[data-user-ids]').attr('data-group-id');
        $('#tab-sprints .panel').each(function() {
            $('.kanban-board', this).remove();
            if (groupId && parseInt(groupId)) {
                let sprintId = $('.list-group', this).attr('data-list-id');
                $('.sprint-board', this).after(
                    $('<a />')
                        .attr('href', BASE + '/kanban/' + groupId + '/' + sprintId)
                        .addClass('kanban-board btn btn-default btn-xs pull-right')
                        .html('<span class=\"fa fa-bars fa-rotate-90\"></span> Kanban')
                );
            }
        });
    }, 10);
});
</script>", 'code', '/^\/backlog/i');
    }
}
