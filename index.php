<?php
include './database/connectBD.php';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();


if (isset($_SESSION['role'])) {
    $role_session = $_SESSION['role'];
    $username = $_SESSION['username'];
} else {
    $role_session = 'Visiteur'; 
}



$habitatQuery = $connect->query(
    "SELECT h.habitat_id, h.nom AS habitat_nom, h.description, h.commentaire_habitat, img.image_data AS video_name
        FROM habitat h
        JOIN comporte c ON h.habitat_id = c.habitat_id
        JOIN image img ON c.image_id = img.image_id"
);


$habitats = [];
while ($row = $habitatQuery->fetch_assoc()) {
    $habitats[$row['habitat_id']] = $row;
    $habitats[$row['habitat_id']]['animals'] = [];
}


$animalQuery = $connect->query("SELECT a.animal_id, a.prenom, a.etat, a.view_nbr, img.image_data AS animal_image, d.habitat_id
                                    FROM animal a
                                    JOIN image img ON a.image_id = img.image_id
                                    JOIN detient d ON a.animal_id = d.animal_id");


$animalIds = [];
while ($row = $animalQuery->fetch_assoc()) {
    $habitats[$row['habitat_id']]['animals'][] = $row;
    $animalIds[] = $row['animal_id']; 
}


$animalIdsString = implode(',', $animalIds);


$rapportQuery = "SELECT rv.date, rv.detail, rv.nourriture, rv.grammage, rv.date_passage, o.animal_id
                        FROM rapport_veterinaire rv
                        JOIN obtient o ON rv.rapport_veterinaire_id = o.rapport_veterinaire_id
                        WHERE o.animal_id IN ($animalIdsString)";
$rapportResult = $connect->query($rapportQuery);


$rapportDetails = [];
while ($row = $rapportResult->fetch_assoc()) {
    $rapportDetails[$row['animal_id']][] = $row; 
}


$avisQuery = $connect->query("SELECT * FROM avis WHERE isVisible = 1");


$avis = [];
while ($row = $avisQuery->fetch_assoc()) {
    $avis[] = $row;
}


function groupArray($array, $size)
{
    $groupedArray = [];
    for ($i = 0; $i < count($array); $i += $size) {
        $groupedArray[] = array_slice($array, $i, $size);
    }
    return $groupedArray;
}


$groupedAvis = groupArray($avis, 3);


$classes = ["", "yellow", "blue"];

$services = [];


$queryService = "SELECT s.service_id, s.nom AS service_name, s.description, img.image_data
        FROM service s
        JOIN image img ON s.image_id = img.image_id";

$result = $connect->query($queryService);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        
        $services[] = [
            'service_id' => htmlspecialchars($row['service_id']),
            'service_name' => htmlspecialchars($row['service_name']),
            'description' => htmlspecialchars($row['description']),
            'image_data' => base64_encode($row['image_data'])
        ];
    }
} else {
    $message = "No services available";
}


$horairesQuery = $connect->query("SELECT DATE_FORMAT(heure_debutt, '%H:%i') AS formatted_debutt, DATE_FORMAT(heure_fin, '%H:%i') AS formatted_fin FROM horaires LIMIT 1");


if ($horairesQuery && $horairesQuery->num_rows > 0) {
    $horaires = $horairesQuery->fetch_assoc();
    $heure_debutt = $horaires['formatted_debutt'];
    $heure_fin = $horaires['formatted_fin'];
} else {
   
    $heure_debutt = '00:00';
    $heure_fin = '00:00';
}


$connect->close();

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="./style/CSS/index.css">
    <link rel="stylesheet" href="./style/CSS/res_index.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js">
    </script>
    <script type="text/javascript">
        (function() {
            emailjs.init({
                publicKey: "dfmyA0T9cT0GgohHu",
            });
        })();
    </script>
    <script src="script.js"></script>
    <script src="./app/index.js" defer></script>
    <link rel="icon" type="image/png" href="assets/icons/logo.PNG">
    <title>ARCADIA</title>
</head>

<body>

    <header>
        <?php if (isset($role_session)) { ?>
            <div class="session_value"><?php echo $role_session ?></div>
        <?php } ?>
        <div class="container">
            <div class="logo"><img src="./assets/icons/logo.PNG" alt="logo fo ARCADIA zoo"></div>
            <div class="nav_bar">

                <a href="index.php">Acceuil</a>
                <a href="#service_page">Services</a>
                <a href="#habitat_page">Habitats</a>
                <a href="#contact">Contact</a>
                <select name="login" id="login">
                    <option id="visiteur_btn" value="Visiteur" <?php echo ($role_session == 'Visiteur') ? 'selected' : ''; ?>>Visiteur</option>
                    <option id="veterinaire_btn" value="veterinaire" <?php echo ($role_session == 'veterinaire') ? 'selected' : ''; ?>>Vétérinaires</option>
                    <option id="employés_btn" value="employés" <?php echo ($role_session == 'employés') ? 'selected' : ''; ?>>Employés</option>
                    <option id="Admin_btn" value="Admin" <?php echo ($role_session == 'Admin') ? 'selected' : ''; ?>>Administrateurs</option>
                    <option id="gestion_admin" class="gestion_admin gestion_admin_hide" value="gestion_admin">Gestion Des Admin</option>
                    <option id="logout" value="logout">deconnecter</option>
                </select>
                <span><img src="./assets/icons/stat_minus_1.svg" width="30" height="30" alt=""></span>

            </div>
        </div>

    </header>

    <section class="main_section">
        <div class="background">
            <video autoplay loop muted>
                <source src="./assets/videos/main_page_video.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
        <article>
            <div class="title">
                <h1>ARCADIA</h1>
            </div>
            <div class="parag">
                <p>
                    Bienvenue au zoo d’Arcadia ,un véritable havre de biodiversité et d’émerveillement situé en France près de la foret Brocéliande ,en bretagne .fondé en 1960,notre zoo abrite une diversité impressionnante d’animaux venus des quartes coins du monde, chaque habitat est conçu pour recréer au mieux les conditions adéquates de chaque animal ,garantissant leur bien être et leurs confort ,y compris la savane africaine vibrante , l’introspection dense de la jungle et les zoo tranquilles des marais.
                </p>
            </div>
        </article>
    </section>

    <section class="countdown-container">
        <button class="edit-time edit-time_hide"><a href="admin/edit_horaires.php">edit time</a></button>

        <div class="store-hours">
            Le zoo est ouvert de <h3 class="start"><?php echo htmlspecialchars($heure_debutt); ?></h3> à <h3 class="end"><?php echo htmlspecialchars($heure_fin); ?></h3>
        </div>

        <div class="store_open"></div>

        <div class="hours-section">

            <div class="hours-container">
                <div class="hours"></div>
                <div class="hours-label">hours</div>
            </div>

            <div class="minutes-container">
                <div class="minutes"></div>
                <div class="minutes-label">minutes</div>
            </div>

            <div class="seconds-container">
                <div class="seconds"></div>
                <div class="seconds-label">seconds</div>
            </div>

        </div>
    </section>

    <section class="habitat_section" id="habitat_page">
        <div class="habitat_title">
            <h1>nous habitats</h1>
        </div>
        <a href="admin/add_habitat.php" class="add_habitat hide_habitat_button">ajouter un habitat</a>
        <a href="admin/add_animal.php" class="add_animal hide_animal_button">ajouter un animal</a>
        <?php foreach ($habitats as $habitat) : ?>
            <div class="habitat_content">
                <div class="right_btn_list_animal"><img src="./assets/icons/arrow_forward_right.svg" alt=""></div>
                <div class="left_btn_list_animal"><img src="./assets/icons/arrow_forward_left.svg" alt=""></div>
                <article>
                    <div class="img">
                        <video autoplay loop muted>
                            <source src="./assets/videos/<?php echo htmlspecialchars($habitat['video_name']); ?>" type="video/mp4">
                        </video>
                    </div>
                    <div class="nom"><?php echo htmlspecialchars($habitat['habitat_nom']); ?></div>
                    <div class="description"><?php echo htmlspecialchars($habitat['description']); ?></div>
                    <div class="commentaire"><?php echo htmlspecialchars($habitat['commentaire_habitat']); ?></div>
                </article>
                <div class="list_animal">
                    <?php foreach ($habitat['animals'] as $animal) : ?>
                        <div class="animal_content" data-animal-id="<?php echo htmlspecialchars($animal['animal_id']); ?>">
                            <div class="img">
                                <?php
                                // Encode the animal image BLOB data as a base64 data URL
                                $animalImageBase64 = base64_encode($animal['animal_image']);
                                ?>
                                <img src="data:image/jpeg;base64,<?php echo $animalImageBase64; ?>" alt="">
                            </div>
                            <div class="details">
                                <div class="prenom"><?php echo htmlspecialchars($animal['prenom']); ?></div>
                                <div class="etat"><?php echo htmlspecialchars($animal['etat']); ?></div>
                                <div class="vetirine_details" id="vetirine_details">
                                    <?php if (isset($rapportDetails[$animal['animal_id']])) : ?>
                                        <?php foreach ($rapportDetails[$animal['animal_id']] as $rapport) : ?>
                                            <p>Date <?php echo htmlspecialchars($rapport['date']); ?></p>
                                            <p>Detail<br> <?php echo htmlspecialchars($rapport['detail']); ?></p>
                                            <p>Nourriture<br> <?php echo htmlspecialchars($rapport['nourriture']); ?></p>
                                            <p>Grammage<br> <?php echo htmlspecialchars($rapport['grammage']); ?></p>
                                            <p>Date Passage <?php echo htmlspecialchars($rapport['date_passage']); ?></p>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <p>No veterinarian reports available.</p>
                                    <?php endif; ?>
                                </div>
                                <div class="more_details">
    <span>
        <p>views</p>
        <p class="view_nbr" data-animal-id="<?php echo htmlspecialchars($animal['animal_id']); ?>">
    
</p>
    </span>
    <p class="more_details_btn">More Details..</p>
</div>
                                <a href="admin/edit_animal.php?id=<?php echo htmlspecialchars($animal['animal_id']); ?>" class="modify_animal hide_animal_button">modifier l'animal</a>
                                <a href="admin/delete_animal.php?id=<?php echo htmlspecialchars($animal['animal_id']); ?>" class="delete_animal hide_animal_button">suprimer l'animal</a>
                                <a href="admin/veterinaire.php?id=<?php echo htmlspecialchars($animal['animal_id']); ?>" class="space_veterinaire hide_animal_button">espace vétérinaire</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="admin/edit_habitat.php?id=<?php echo htmlspecialchars($habitat['habitat_id']); ?>" class="modify_habitat hide_habitat_button">modifier l'habitat</a>
                <a href="admin/delete_habitat.php?id=<?php echo htmlspecialchars($habitat['habitat_id']); ?>" class="delete_habitat hide_habitat_button">suprimer l'habitat</a>
            </div>
        <?php endforeach; ?>

    </section>

    <section class="service_section" id="service_page">
        <div class="service_title">
            <h1>nous services</h1>
        </div>

        <div class="right_btn"><img src="./assets/icons/arrow_forward_right.svg" alt=""></div>
        <div class="left_btn"><img src="./assets/icons/arrow_forward_left.svg" alt=""></div>
        <div class="add_service add_service_hide"><a href="admin/add_service.php">+Add service</a></div>

        <div class="service_list">

            <?php if (!empty($services)): ?>
                <?php foreach ($services as $service): ?>
                    <div class="service_content">
                        <div class="img">
                            <img src="data:image/jpeg;base64, <?php echo $service['image_data']; ?>" alt="<?php echo $service['service_name']; ?>">
                        </div>
                        <div class="content">
                            <h3><?php echo $service['service_name']; ?></h3>
                            <p><?php echo $service['description']; ?></p>
                        </div>
                        <button class="modify_service buttons_service_hide"><a href="admin/edit_service.php?id=<?php echo $service['service_id']; ?>">modifier service</a></button>
                        <button class="delete_service buttons_service_hide"><a href="admin/delete_service.php?id=<?php echo $service['service_id']; ?>" onclick="return confirm('Are you sure you want to delete this service?');">Supprimer service</a></button>
                    </div>
                <?php endforeach; ?>
            <?php else: echo ('<p>' . $message . '</p>');
            endif; ?>

        </div>
    </section>

    <section class="avis_section" id="avis_section">

        <div class="avis_container">
            <div class="container">
                <h1>Avis des visiteurs</h1>
                <div class="avis_title">
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                </div>
                <div class="show_all_avis show_all_avis_hide">
                    <button id="all_avis">see all avis</button>
                </div>

                <?php foreach ($groupedAvis as $avisChunk) : ?>
                    <div class="avis-grid">
                        <?php foreach ($avisChunk as $index => $avis) : ?>
                            <div class="avis <?php echo $classes[$index % count($classes)]; ?>">
                                <div class="quote-icon"><img src="./assets/icons/format_quote.svg" alt=""></div>
                                <h3><?php echo htmlspecialchars($avis['pseudo']); ?></h3>
                                <p><?php echo htmlspecialchars($avis['commentaire']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <div class="buttons">
                    <button id="show_more" class="show_more">show more... </button>
                </div>
            </div>
            <div id="add_new_avis" class="add_new_avis">
                <img src="./assets/icons/notebook.gif" alt="">
            </div>
            <div id="new_avis" class="new_avis">
                <form action="" method="post" id="form_quote">
                    <input type="text" name="pseudo" id="" placeholder="your name">
                    <textarea name="commentaire" id="" placeholder="your quote here"></textarea>
                    <input type="hidden" name="action" value="add_avis">
                    <button id="send_quote_btn">send your quote</button>
                </form>
            </div>
            <div class="check_send_quote">
                <p>we got it, thanks</p>
            </div>
        </div>

    </section>

    <footer id="contact">
        <section class="contact">
            <div class="container">
                <h1>Contact</h1>
                <p>We love to solve problems so here is how we solved</p>
                <img src="./assets/images/contact_img.jpg" alt="Image for contact">
            </div>
        </section>

        <section class="form-section">
            <div class="container">
                <h2>Hello</h2>
                <p>How can we help you?</p>
                <form id="form_contact">
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" require>
                    </div>
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" require>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" require>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" require></textarea>
                    </div>
                    <button id="check_message" type="submit">send message</button>
                </form>
            </div>
        </section>

        <section class="offices">
            <div class="container">
                <h2>Our Offices</h2>
                <div class="office">
                    <h3>Paris</h3>
                    <p>contact@freemail.com<br>+33 786 56 78<br>241 Crown Ovation, Paris</p>
                </div>
                <div class="office">
                    <h3>London</h3>
                    <p>contact@freemail.com<br>+33 786 56 78<br>241 Crown Ovation, London</p>
                </div>
            </div>
        </section>

        <section class="footer">
            <div class="container">
                <div class="footer-content">
                    <div class="logo">AGENCY</div>
                    <div class="important">
                        <h4>Important</h4>
                        <p>Some important links and information here.</p>
                    </div>
                    <div class="social">
                        <h4>Social</h4>
                        <ul>
                            <li><a href="https://www.facebook.com/" target="_blank"><i class="fab fa-facebook" aria-hidden="true"></i></a></li>
                            <li><a href="https://x.com/" target="_blank"><i class="fab fa-twitter" aria-hidden="true"></i></a></li>
                            <li><a href="https://www.youtube.com/" target="_blank"><i class="fab fa-youtube" aria-hidden="true"></i></a></li>
                            <li><a href="https://www.linkedin.com/" target="_blank"><i class="fab fa-linkedin" aria-hidden="true"></i></a></li>
                            <li><a href="https://www.instagram.com/" target="_blank"><i class="fab fa-instagram" aria-hidden="true"></i></a></li>
                        </ul>
                    </div>
                    <div class="contact_bar">
                        <h4>Contact</h4>
                        <p>Contact details here.</p>
                    </div>
                </div>
                <p class="disclaimer">Tout droit réservé.</p>
            </div>
        </section>
    </footer>

</body>

</html>