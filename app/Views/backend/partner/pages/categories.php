<div class="main-content">
    <section class="section">
        <div class="section-header mt-2">
            <h1><?= labels('categories', "Categories") ?></h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= base_url('/admin/dashboard') ?>"><i class="fas fa-home-alt text-primary"></i> <?= labels('Dashboard', 'Dashboard') ?></a></div>
                <div class="breadcrumb-item"> <?= labels('category', 'Categories') ?></a></div>
            </div>
        </div>
        <div class="container-fluid card mt-2">
            <div class="row">
                <div class="col-md">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class=" mb-3 row">
                                    <div class="col-md-4 col-sm-2 mb-2">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="customSearch" placeholder="Search here!" aria-label="Search" aria-describedby="customSearchBtn">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="button">
                                                    <i class="fa fa-search d-inline"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <button class="btn btn-secondary  ml-2 filter_button" id="filterButton">
                                        <span class="material-symbols-outlined mt-1">
                                            filter_alt
                                        </span>
                                    </button>
                                    <div class="dropdown d-inline ml-2">
                                        <button class="btn export_download dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <?= labels('download', 'Download') ?> 
                                        </button>
                                        <div class="dropdown-menu" x-placement="bottom-start" style="position: absolute; transform: translate3d(0px, 28px, 0px); top: 0px; left: 0px; will-change: transform;">
                                            <a class="dropdown-item" onclick="custome_export('pdf','Category list','cash_collection');"><?= labels('pdf', 'PDF') ?> </a>
                                            <a class="dropdown-item" onclick="custome_export('excel','Category list','cash_collection');"><?= labels('excel', 'Excel') ?> </a>
                                            <a class="dropdown-item" onclick="custome_export('csv','Category list','cash_collection')"><?= labels('csv', 'CSV') ?> </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-borderd"  data-fixed-columns="true"  id="cash_collection" data-query-params="category_query_params" data-pagination-successively-size="2" data-show-export="false" data-export-types="['txt','excel','csv']" data-export-options='{"fileName": "invoice-order-list","ignoreColumn": ["action"]}' data-auto-refresh="true" data-show-columns="false" data-search="false" data-show-refresh="false" data-toggle="table" data-page-list="[5, 10, 25, 50, 100, 200, All]" data-side-pagination="server" data-pagination="true" data-url="<?= base_url("admin/categories/list") ?>" data-sort-name="id" data-sort-order="desc">
                                        <thead>
                                            <tr>
                                                <th data-field="id" class="text-center" data-sortable="true"><?= labels('id', 'ID') ?></th>
                                                <th data-field="category_image" class="text-center"><?= labels('image', 'Image') ?></th>
                                                <th data-field="name" class="text-center" data-sortable="true"><?= labels('name', 'Name') ?></th>
                                                <th data-field="created_at" class="text-center" data-sortable="true"><?= labels('created_at', 'Created At') ?></th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </section>
</div>
<div id="filterBackdrop"></div>
<div class="drawer" id="filterDrawer">
    <section class="section">
        <div class="row">
            <div class="col-md-12">
                <div class="bg-new-primary" style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center;">
                        <div class="bg-white m-3 text-new-primary" style="box-shadow: 0px 8px 26px #00b9f02e; display: inline-block; padding: 10px; height: 45px; width: 45px; border-radius: 15px;">
                            <span class="material-symbols-outlined">
                                filter_alt
                            </span>
                        </div>
                        <h3 class="mb-0" style="display: inline-block; font-size: 16px; margin-left: 10px;"><?= labels('filters', 'Filters') ?></h3>
                    </div>
                    <div id="cancelButton" style="cursor: pointer;">
                        <span class="material-symbols-outlined mr-2">
                            cancel
                        </span>
                    </div>
                </div>
                <div class="row mt-4 mx-2">
                    <div class="col-md-12">
                        <div class="form-group ">
                            <label for="table_filters"><?= labels('table_filters', 'Table filters') ?></label>
                            <div id="columnToggleContainer">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<script>
    $(document).ready(function() {
    for_drawer("#filterButton", "#filterDrawer", "#filterBackdrop", "#cancelButton");
    var columns = [{
                field: 'id',
                label: '<?= labels('id', 'ID') ?>',
            },
            {
                field: 'category_image',
                label: '<?= labels('image', 'Image') ?>'
            },
            {
                field: 'name',
                label: '<?= labels('name', 'Name') ?>'
            },
            {
                field:'created_at',
                label:"<?= labels('created_at', 'Created At') ?>",
            }
        ];
    setupColumnToggle('cash_collection', columns, 'columnToggleContainer');
});
</script>
<script>
    $("#customSearch").on('keydown', function() {
        $('#cash_collection').bootstrapTable('refresh');
    });
    function category_query_params(p) {
        return {
            search: $("#customSearch").val() ? $("#customSearch").val() : p.search,
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
        };
    }
</script>