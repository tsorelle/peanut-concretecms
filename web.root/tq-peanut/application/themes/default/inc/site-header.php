<header class="p-1 text-dark">
    <nav id="top-navbar" class="navbar navbar-expand-lg navbar-light bg-light"
         aria-label="Top navigation">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">
                <?php
                    printf('<img style="height:3rem" class="img img-fluid" src="%s/assets/img/peanut-logo-6.jpg">',
                            URL_APPLICATION)
                ?>

            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#nutshell-top-navigation-menu"
                    aria-controls="nutshell-top-navigation-menu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="nutshell-top-navigation-menu">
                <ul class="navbar-nav me-auto mb-2 mb-sm-0">


                <?php
                    /** @var \Nutshell\cms\SiteMap $sitemap */
                    $sitemap->printTopMenu();
                ?>

                </ul>

                <form id="song-search" method="post" action="/songs" >
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search" autocomplete="off"
                               id="searchstring" name="searchstring"></input>
                        <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </nav>

</header>

