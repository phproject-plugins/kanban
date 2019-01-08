Vue.component('kanban-board', {
    props: {
        groupId: Number,
        sprintId: Number
    },
    data: function () {
        return {
            swimlanes: [],
            issues: [],
            isLoading: true,
        };
    },
    template: '\
        <div class="kanban-board" :class="isLoading && \'is-loading\'">\
            <div class="panel panel-default kanban-swimlane"\
                :data-id="lane.id"\
                v-for="lane in swimlanes">\
                <div class="panel-heading">\
                    {{ lane.name }}\
                </div>\
                <div class="panel-body">\
                    <draggable\
                        :list="issuesByStatus(lane.id)"\
                        :options="{group: \'kanban\', draggable: \'.kanban-issue\'}"\
                        @end="onIssueMove">\
                        <kanban-issue\
                            v-for="issue in issuesByStatus(lane.id)"\
                            :key="issue.id"\
                            :issue="issue" />\
                    </draggable>\
                </div>\
            </div>\
        </div>',
    methods: {
        onIssueMove: function (event) {
            let status = $(event.to).closest('.kanban-swimlane').attr('data-id');
            var index = null;
            var issueId = $(event.item).attr('data-id');
            this.issues.some(function(issue, i) {
                if (issue.id == issueId) {
                    index = i;
                    return true;
                }
            });
            let issue = this.issues[index];

            // TODO: update sort on backend
            this.$set(issue, 'status', status);
            $.post(BASE + '/kanban/move/' + issue.id, {
                status: status,
            });
        },
        issuesByStatus: function (statusId) {
            return this.issues.filter(function (issue) {
                return issue.status == statusId;
            });
        },
    },
    mounted: function () {
        var vm = this,
            group = this.$props.groupId,
            sprint = this.$props.sprintId;
        $.get(BASE + '/kanban/boardLanes', function (data) {
            vm.swimlanes = data;
            $.get(BASE + '/kanban/boardData', {
                group: group,
                sprint: sprint,
            }, function (data) {
                vm.issues = data;
                vm.isLoading = false;
            }, 'json');
        }, 'json');
    }
});

Vue.component('kanban-issue', {
    props: {
        issue: Object
    },
    template: '\
        <div class="panel panel-default kanban-issue"\
            :style="\'border-left-color: #\' + issue.owner_task_color"\
            :data-id="issue.id">\
            <div class="panel-body">\
                {{ issue.name }}<br>\
                <small>{{ issue.owner_name }}</small>\
            </div>\
        </div>'
});

var app = new Vue({
    el: '#root'
});
