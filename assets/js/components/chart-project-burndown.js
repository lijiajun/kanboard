KB.component('chart-project-burndown', function (containerElement, options) {

    this.render = function () {
        var metrics = options.metrics;
        var columns = [];
        var categories = [];
        var inputFormat = d3.time.format("%Y-%m-%d");
        var outputFormat = d3.time.format(options.dateFormat);

        for (var i = 0; i < metrics.length; i++) {
            for (var j = 0; j < metrics[i].length; j++) {
                var currentValue = metrics[i][j];

                if (i === 0) {
                    if (j > 0) {
                        columns.push([currentValue]);
                    }
                } else {
                    if (j > 0) {
                        columns[j-1].push(currentValue);
                    } else {
                        categories.push(outputFormat(inputFormat.parse(currentValue)));
                    }
                }
            }
        }

        KB.dom(containerElement).add(KB.dom('div').attr('id', 'chart').build());

        c3.generate({
            data: {
                columns: columns
            },
            axis: {
                x: {
                    type: 'category',
                    categories: categories
                }
            }
        });
    };
});
