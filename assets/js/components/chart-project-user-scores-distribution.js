KB.component('chart-project-user-scores-distribution', function (containerElement, options) {

    this.render = function () {
        var columns = [];

        for (var i = 0; i < options.metrics.length; i++) {
            columns.push([options.metrics[i].user, options.metrics[i].nb_scores]);
        }

        KB.dom(containerElement).add(KB.dom('div').attr('id', 'chart').build());

        c3.generate({
            data: {
                columns: columns,
                type : 'donut'
            }
        });
    };
});
