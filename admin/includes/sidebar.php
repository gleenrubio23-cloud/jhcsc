<link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=logout" />


<div class="sidebar" data-color="green" data-background-color="black" data-image="../assets/img/xx.jpg">
    <!--
        Tip 1: You can change the color of the sidebar using: data-color="purple | azure | green | orange | danger"

        Tip 2: you can also add an image using data-image tag
    -->
    <div class="logo">
        <a href="http://www.creative-tim.com" class="simple-text logo-mini">
            <img src="../assets/img/guidance logo.png" alt="..." class="img-just-icon" style="width: 25px" />
        </a>
        <a href="http://www.creative-tim.com" class="simple-text logo-normal">
            ADMIN
        </a>
    </div>
    <div class="sidebar-wrapper">
        <div class="user">
            <div class="photo">
                <!--<img src="../assets/img/faces/avatar.jpg" />-->
            </div>
            <div class="user-info">
                <a data-toggle="collapse" href="#collapseExample" class="username">
                    <span>
                        user
                        <b class="caret"></b>
                    </span>
                </a>
                <div class="collapse" id="collapseExample">
                    <ul class="nav">
                        <!--<li class="nav-item">
                            <a class="nav-link" href="#">
                                <span class="sidebar-mini"> MP </span>
                                <span class="sidebar-normal"> My Profile </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <span class="sidebar-mini"> S </span>
                                <span class="sidebar-normal"> Settings </span>
                            </a>
                        </li>-->
                        <li class="nav-item">
                            <a class="nav-link" href="../functions/logout.php">
                                <span class="sidebar-mini">
                                    <span class="material-symbols-outlined">
                                        logout
                                    </span>
                                </span>
                                <span class="sidebar-normal"> Logout </span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <ul class="nav">
            <li class="nav-item ">
                <a class="nav-link" href="index.php">
                    <i class="material-icons">dashboard</i>
                    <p>Dashboard</p>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#pagesExamples">
                    <i class="material-icons">backpack</i>
                    <p>
                        Student
                        <b class="caret"></b>
                    </p>
                </a>
                <div class="collapse" id="pagesExamples">
                    <ul class="nav">
                        <li class="nav-item">
                            <!-- Link for First Year students -->
                            <a class="nav-link" href="studentList.php?year=1">
                                <span class="sidebar-mini"> 1 </span>
                                <span class="sidebar-normal"> First Year </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <!-- Link for Second Year students -->
                            <a class="nav-link" href="studentList.php?year=2">
                                <span class="sidebar-mini"> 2 </span>
                                <span class="sidebar-normal"> Second Year </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <!-- Link for Third Year students -->
                            <a class="nav-link" href="studentList.php?year=3">
                                <span class="sidebar-mini"> 3 </span>
                                <span class="sidebar-normal"> Third Year </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <!-- Link for Fourth Year students -->
                            <a class="nav-link" href="studentList.php?year=4">
                                <span class="sidebar-mini"> 4 </span>
                                <span class="sidebar-normal"> Fourth Year </span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#componentsExamples">
                    <i class="material-icons">warning</i>
                    <p>
                        Reported Cases
                        <b class="caret"></b>
                    </p>
                </a>
                <div class="collapse" id="componentsExamples">
                    <ul class="nav">
                        <li class="nav-item">
                            <a class="nav-link" href="reported_violation.php?course=BSIT">
                                <span class="sidebar-mini"> IT </span>
                                <span class="sidebar-normal"> BS InfoTech </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reported_violation.php?course=BIT">
                                <span class="sidebar-mini"> B </span>
                                <span class="sidebar-normal"> Indu Tech </span>
                            </a>
                        </li>
                        <!--
                        <li class="nav-item">
                            <a class="nav-link" href="reported_violation.php?course=BTVTED">
                                <span class="sidebar-mini"> TE </span>
                                <span class="sidebar-normal">BTVTED </span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="reported_violation.php?course=BTLED">
                                <span class="sidebar-mini"> LE </span>
                                <span class="sidebar-normal"> Livelihood Education </span>
                            </a>
                        </li>-->
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="users_list.php">
                    <i class=" material-icons">diversity_3</i>
                    <p>Users</p>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="Archive_Violation.php?status_name=Resolved">
                    <i class="material-icons">archive</i>
                    <p>Archive</p>
                </a>
            </li>
        </ul>
    </div>
</div>