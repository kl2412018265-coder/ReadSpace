-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 02, 2026 at 08:54 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `readspace_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(150) NOT NULL,
  `genre` varchar(50) NOT NULL,
  `summary` text DEFAULT NULL,
  `cover_image_url` varchar(255) DEFAULT NULL,
  `is_mature_content` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `title`, `author`, `genre`, `summary`, `cover_image_url`, `is_mature_content`, `created_at`) VALUES
(3, 'Dr Alpha Male', 'Anjell', 'Romance,Comedy,Fiction', 'Cerita bermula apabila Tristin Hazel tidak sengaja melanggar seorang lelaki sombong dan penuh keyakinan diri di depan sebuah kedai. Lelaki itu melemparkan kata-kata pedas dan menyakitkan hati Hazel, membuatkan Hazel membalas kembali kerana dia seorang yang pantang mengalah.', 'uploads/covers/2972070aba63ca78e6d0b833d440b798.jpg', 0, '2026-09-27 17:58:10'),
(4, 'Love Hypothesis', 'Ali hazelwood', 'Romance', 'The Love Hypothesis follows the main character graduate student Olive at Stanford University as she pretends to date biology professor Adam Carlsen to convince her friend Anh that her dating life is going well. While trying to convince everyone around them that they are in love, Olive and Adam forget that their feelings are supposed to be fake', 'uploads/covers/82348edfeadd65aa2c0eda2886dfdb82.webp', 1, '2026-09-27 18:53:55'),
(5, 'The Hunger Games', 'Suzanne Collins', 'Young Adult, Fiction, Fantasy', 'Winning means fame and fortune. Losing means certain death. The Hunger Games have begun. . . .\r\n\r\nIn the ruins of a place once known as North America lies the nation of Panem, a shining Capitol surrounded by twelve outlying districts. The Capitol is harsh and cruel and keeps the districts in line by forcing them all to send one boy and one girl between the ages of twelve and eighteen to participate in the annual Hunger Games, a fight to the death on live TV.\r\n\r\nSixteen-year-old Katniss Everdeen regards it as a death sentence when she steps forward to take her sister\'s place in the Games. But Katniss has been close to dead before-and survival, for her, is second nature. Without really meaning to, she becomes a contender. But if she is to win, she will have to start making choices that weigh survival against humanity and life against love.', 'uploads/covers/8cb3265f83e277fe1ab3d633a9cad1a6.jpg', 0, '2026-09-29 13:47:49'),
(6, 'To Kill a Mockingbird', 'Harper Lee', 'Classic, Historical Fiction, Literature', '\"Shoot all the bluejays you want, if you can hit \'em, but remember it\'s a sin to kill a mockingbird.\"\r\n\r\nA lawyer\'s advice to his children as he defends the real mockingbird of Harper Lee\'s classic novel - a black man charged with the rape of a white girl. Through the young eyes of Scout and Jem Finch, Harper Lee explores with exuberant humour the irrationality of adult attitudes to race and class in the Deep South of the 1930s. The conscience of a town steeped in prejudice, violence and hypocrisy is pricked by the stamina of one man\'s struggle for justice. But the weight of history will only tolerate so much.\r\n\r\n\"To Kill A Mockingbird\" became both an instant bestseller and a critical success when it was first published in 1960. It went on to win the Pulitzer Prize in 1961 and was later made into an Academy Award-winning film.', 'uploads/covers/53c27ebc368a09b5a967aef44e68f26d.jpg', 0, '2026-09-29 13:51:57'),
(7, 'To all the Boys I\'ve Loved Before', 'Jenny Han', 'Romance, Young Adult', 'To All the Boys I’ve Loved Before is the story of Lara Jean, who has never openly admitted her crushes, but instead wrote each boy a letter about how she felt, sealed it, and hid it in a box under her bed.\r\n\r\nBut one day Lara Jean discovers that somehow her secret box of letters has been mailed, causing all her crushes from her past to confront her about the letters: her first kiss, the boy from summer camp, even her sister\'s ex-boyfriend, Josh.\r\n\r\nAs she learns to deal with her past loves face to face, Lara Jean discovers that something good may come out of these letters after all.', 'uploads/covers/819ad5b38f5ac6850fe572dbbb08e2a9.jpg', 0, '2026-09-29 16:27:27'),
(8, 'P.S. I Still Love You', 'Jenny Han', 'Romance, Young Adult', 'Lara Jean didn’t expect to really fall for Peter.\r\n\r\nThey were just pretending. Until they weren’t. And now Lara Jean has to learn what it\'s like to be in a real relationship and not just a make-believe one.\r\n\r\nBut when another boy from her past returns to her life, Lara Jean’s feelings for him suddenly return too. Can a girl be in love with two boys at once?\r\n\r\nIn this charming and heartfelt sequel to the New York Times bestseller To All the Boys I\'ve Loved Before, Lara Jean is about to find out that falling in love is the easy part.', 'uploads/covers/b08b678a5d1c78cadf9bd9fdbfe65370.jpg', 0, '2026-09-29 16:30:02'),
(9, 'Always and Forever, Lara Jean', 'Jenny Han', 'Romance, Young Adult', 'Lara Jean is having the best senior year.\r\n\r\nAnd there’s still so much to look forward to: a class trip to New York City, prom with her boyfriend Peter, Beach Week after graduation, and her dad’s wedding to Ms. Rothschild. Then she’ll be off to college with Peter, at a school close enough for her to come home and bake chocolate chip cookies on the weekends.\r\n\r\nLife couldn’t be more perfect!\r\n\r\nAt least, that’s what Lara Jean thinks . . . until she gets some unexpected news.\r\n\r\nNow the girl who dreads change must rethink all her plans—but when your heart and your head are saying two different things, which one should you listen to?', 'uploads/covers/042f5b0f9539f915442388e20c6bb7c4.jpg', 0, '2026-09-29 16:31:31'),
(10, 'The Summer I Turned Pretty', 'Jenny Han', 'Romance, Young Adult', 'Belly measures her life in summers. Everything good, everything magical happens between the months of June and August. Winters are simply a time to count the weeks until the next summer, a place away from the beach house, away from Susannah, and most importantly, away from Jeremiah and Conrad. They are the boys that Belly has known since her very first summer—they have been her brother figures, her crushes, and everything in between. But one summer, one terrible and wonderful summer, the more everything changes, the more it all ends up just the way it should have been all along.', 'uploads/covers/510f25dfd5ebb3128bb4902653b21d5c.jpg', 0, '2026-09-29 16:32:36'),
(11, 'It\'s Not Summer Without You', 'Jenny Han', 'Romance, Young Adult', 'Belly finds out what comes after falling in love in this follow-up to The Summer I Turned Pretty from the New York Times bestselling author of To All the Boys I’ve Loved Before (soon to be a major motion picture!), Jenny Han.\r\n\r\nCan summer be truly summer without Cousins Beach?\r\n\r\nIt used to be that Belly counted the days until summer, until she was back at Cousins Beach with Conrad and Jeremiah. But not this year. Not after Susannah got sick again and Conrad stopped caring. Everything that was right and good has fallen apart, leaving Belly wishing summer would never come.\r\n\r\nBut when Jeremiah calls saying Conrad has disappeared, Belly knows what she must do to make things right again. And it can only happen back at the beach house, the three of them together, the way things used to be. If this summer really and truly is the last summer, it should end the way it started—at Cousins Beach.', 'uploads/covers/d82e693ece87904a3203acf71b0f3594.jpg', 0, '2026-09-29 16:34:09'),
(12, 'We\'ll Always Have Summer', 'Jenny Han', 'Romance, Young Adult', 'It\'s been two years since Conrad told Belly to go with Jeremiah. She and Jeremiah have been inseparable ever since, even attending the same college—only, their relationship hasn\'t exactly been the happily ever after Belly had hoped it would be. And when Jeremiah makes the worst mistake a boy can make, Belly is forced to question what she thought was true love. Does she really have a future with Jeremiah? Has she ever gotten over Conrad? It\'s time for Belly to decide, once and for all, who has her heart forever.', 'uploads/covers/7f1d8884add327d6d69dd5362c005d8e.jpg', 0, '2026-09-29 16:35:13'),
(13, 'Better Than the Movies', 'Lynn Painter', 'Romance, Enemies To Lovers', 'In this rom-com about rom-coms, in the spirit of Kasie West and Jenn Bennett, a hopeless romantic teen attempts to secure a happily-ever-after moment with her forever crush, but finds herself reluctantly drawn to the boy next door.\r\n\r\nPerpetual daydreamer Liz Buxbaum gave her heart to Michael a long time ago. But her cool, aloof forever crush never really saw her before he moved away. Now that he’s back in town, Liz will do whatever it takes to get on his radar - and maybe snag him as a prom date - even befriend Wes Bennet.\r\n\r\nThe annoyingly attractive next-door neighbor might seem like a prime candidate for romantic-comedy fantasies, but Wes has only been a pain in Liz’s butt since they were kids. Pranks involving frogs and decapitated lawn gnomes do not a potential boyfriend make. Yet, somehow, Wes and Michael are hitting it off, which means Wes is Liz’s in.\r\n\r\nBut as Liz and Wes scheme to get Liz noticed by Michael so she can have her magical prom moment, she’s shocked to discover that she likes being around Wes. And as they continue to grow closer, she must reexamine everything she thought she knew about love - and rethink her own ideas of what happily ever after should look like.', 'uploads/covers/1192aa5853fa9c5abbf3bde1a79d13d3.jpg', 0, '2026-09-29 16:37:48'),
(14, 'Nothing Like the Movies', 'Lynn Painter', 'Romance, Young Adult', 'For a few beautiful months, Wes had his dream girl: the strong-willed girl next door Liz. But right as the two were about to set off for college to start their freshman year together, tragedy struck. Wes was left dealing with the fallout, which ultimately meant losing Liz in the process.\r\n\r\nFlash forward months later, and Wes and Liz find themselves in college, together. In a healthier place now, Wes knows he broke Liz’s heart when he ended things, but he is determined to make her fall back in love with him.\r\n\r\nWes knows Liz better than anyone, and he has a foolproof plan to win her back with rom-com-worthy grand gestures she loves. Only . . . Liz will have none of it! Wes has to scheme like a rom-com hero to figure out how to see her. Even worse, Liz has a new friend . . . a guy friend.\r\n\r\nStill, Wes won’t give up, adapting his clever plans and going hard to get Liz’s attention and win back her affection. But after his best efforts get him nowhere, Wes is left wondering if their relationship is really over for good.', 'uploads/covers/d6c698939f28f9ef24130932baef8bbd.jpg', 0, '2026-09-29 16:39:22'),
(15, 'The Do-Over', 'Lynn Painter', 'Romance, Young Adult', 'In this riotous young adult romp for fans of Recommended for You and A Cuban Girl’s Guide to Tea and Tomorrow, a teen girl has the worst Valentine’s Day ever—only to relive it over and over again.\r\n\r\nAfter living through a dumpster fire of a Valentine’s Day, Emilie Hornby escapes to her grandmother’s house for some comfort and a consolation pint of Ben & Jerry’s. She passes out on the couch, but when she wakes up, she’s back home in her own bed—and it’s Valentine’s Day all over again. And the next day? Another nightmare V-Day.\r\n\r\nEmilie is stuck in some sort of time loop nightmare that she can’t wake up from as she re-watches her boyfriend, Josh, cheat on her day after day. In addition to Josh’s recurring infidelity, Emilie can’t get away from the enigmatic Nick, who she keeps running into—sometimes literally—in unfortunate ways.\r\n\r\nHow many days can one girl passively watch her life go up in flames? And when something good starts to come out of these terrible days, what happens when the universe stops doling out do-overs?', 'uploads/covers/36ba428effeb502d5087669126a1a8a2.jpg', 0, '2026-09-29 16:42:03'),
(16, 'Betting on You', 'Lynn Painter', 'Romance, Young Adult', 'When seventeen-year-old Bailey starts a new job at a hotel waterpark, she is less than thrilled to see an old acquaintance is one of her coworkers. Bailey met Charlie a year ago on the long flight to Omaha, where she moved after her parents’ divorce. Charlie’s cynicism didn’t mix well with Bailey’s carefully well-behaved temperament, and his endless commentary was the irritating cherry on top of an already emotionally fraught trip.\r\n\r\nNow, Bailey and Charlie are still polar opposites, but instead of everything about him rubbing Bailey the wrong way, she starts to look forward to hanging out and gossiping about the waterpark guests and their coworkers—particularly two who keep flirting with each other. Bailey and Charlie make a bet on whether or not the cozy pair will actually get together. Charlie insists that members of the opposite sex can’t just be friends, and Bailey is determined to prove him wrong.\r\n\r\nBailey and Charlie keep close track of the romantic progress of others while Charlie works to deflect the growing feelings he’s developed for Bailey. Terrified to lose her if his crush becomes known, what doesn’t help his agenda is Bailey and Charlie “fake dating” in order to disrupt the annoying pleasantries between Bailey’s mom and her mom’s new boyfriend. Soon, what Charlie was hoping to avoid becomes a reality as Bailey starts to see him as not only a friend she can rely on in the midst of family drama—but someone who makes her hands shake and heart race. But Charlie has a secret—a secret that involves Bailey and another bet Charlie may have made. Can the two make a real go of things…or has Charlie’s secret doomed them before they could start?', 'uploads/covers/010f933a9ee7366ba12a15d629d1adb1.jpg', 0, '2026-09-29 16:43:07'),
(17, 'The Fault in Our Stars', 'John Green', 'Romance, Young Adult', 'Despite the tumor-shrinking medical miracle that has bought her a few years, Hazel has never been anything but terminal, her final chapter inscribed upon diagnosis. But when a gorgeous plot twist named Augustus Waters suddenly appears at Cancer Kid Support Group, Hazel\'s story is about to be completely rewritten.\r\n\r\nInsightful, bold, irreverent, and raw, The Fault in Our Stars is award-winning author John Green\'s most ambitious and heartbreaking work yet, brilliantly exploring the funny, thrilling, and tragic business of being alive and in love.', 'uploads/covers/f7d1bc0eaa0bbda69084fbfaa1ef3132.jpg', 0, '2026-09-29 16:45:47'),
(19, 'The Sun Is Also a Star', 'Nicola Yoon', 'Romance, Young Adult', 'Natasha: I’m a girl who believes in science and facts. Not fate. Not destiny. Or dreams that will never come true. I’m definitely not the kind of girl who meets a cute boy on a crowded New York City street and falls in love with him. Not when my family is twelve hours away from being deported to Jamaica. Falling in love with him won’t be my story.\r\n\r\nDaniel: I’ve always been the good son, the good student, living up to my parents’ high expectations. Never the poet. Or the dreamer. But when I see her, I forget about all that. Something about Natasha makes me think that fate has something much more extraordinary in store—for both of us.\r\n\r\nThe Universe: Every moment in our lives has brought us to this single moment. A million futures lie before us. Which one will come true?', 'uploads/covers/4097e9fefcfb784f637dd2c0687eef46.jpg', 0, '2026-09-29 16:49:22'),
(20, 'Everything, Everything', 'Nicola Yoon', 'Romance, Young Adult', 'My disease is as rare as it is famous. It’s a form of Severe Combined Immunodeficiency, but basically, I’m allergic to the world. I don’t leave my house, have not left my house in fifteen years. The only people I ever see are my mom and my nurse, Carla.\r\n\r\nBut then one day, a moving truck arrives. New next door neighbors. I look out the window, and I see him. He’s tall, lean and wearing all black—black t-shirt, black jeans, black sneakers and a black knit cap that covers his hair completely. He catches me looking and stares at me. I stare right back. His name is Olly. I want to learn everything about him, and I do. I learn that he is funny and fierce. I learn that his eyes are Atlantic Ocean-blue and that his vice is stealing silverware. I learn that when I talk to him, my whole world opens up, and I feel myself starting to change—starting to want things. To want out of my bubble. To want everything, everything the world has to offer.\r\n\r\nMaybe we can’t predict the future, but we can predict some things. For example, I am certainly going to fall in love with Olly. It’s almost certainly going to be a disaster.', 'uploads/covers/9307daba92b559e0b3168886740f5b4c.jpg', 0, '2026-09-29 16:50:40'),
(21, 'Si Bakhil dengan Bunganya', 'Liza Nur', 'Romance', 'Takdir menemukan seorang jejaka bersifat bakhil, Baqi dengan Kekwa, seorang gadis ‘kekwat’, di bumi asing dalam kehidupan yang ralat untuk mereka tempuhi sendiri. Lantas mereka mencipta hubungan aneh yakni sahabat yang saling mempergunakan demi kepentingan diri masing-masing. Baqi hidup bertunjangkan misi untuk membalas dendam terhadap ayah saudaranya, Abu Jalal.\r\n\r\nKekwa pula ada kisah hitam dan dendamnya yang tersendiri terhadap ibu tiri dan ayahnya. Baqi disuruh menggoda adik sepupu Kekwa, Orked untuk menghuru-harakan hubungannya dengan tunangnya, Uwais. Sebagai balasan, Kekwa pula bersetuju berlakon menjadi kekasih Baqi dan mengikutnya pulang ke ladang kelapa sawit untuk berperang dengan Abu Jalal.\r\n\r\nTanpa mereka sedari, dalam misi membalas dendam, mereka saling terjatuh hati antara satu sama lain. Bermulalah adegan gila menafikan gelodak di jiwa masing-masing. Kekwa yang takut untuk kehilangan lagi tidak ampuh menerima Baqi. Baqi pula terjerat antara rasa suka dan misinya. Saat nyawa jadi taruhan, perhubungan mereka terus teruji.\r\n\r\n“Gunakan ajelah aku. Lepas tu buang aku. Macam yang orang lain buat. And trust me, Bakhil… Aku akan buat benda yang sama dengan kau.”- Kekwa\r\n\r\n“Aku boleh pastikan apa yang kau nak adalah aku, hala tuju kau adalah pangkuan aku dan tempat tinggal kau adalah rumah aku. Tapi boleh tak kau jujur dengan perasaan kau?” – Baqi', 'uploads/covers/9b3a5174fc5dcb08b05009dd57b08638.jpg', 0, '2026-09-29 18:09:41'),
(22, 'Syurga itu Bernama Ibu', 'Osman Deen', 'Family Drama, Heartwarming', 'Wahid, Hanani, Ilyasa. Jik tidak cukup seorang, tidak lengkaplah mereka bertiga. Wahid dapat melihat bayang, Hanani mendengar suara, dan Ilyasa menghidu bau, dari memori masa lalu.\r\nNamun, selepas arwah ayah meninggal, kuasa yang pernah mengikat hubungan adik-beradix menjadi sumpahan yang melukakan. Lantas Wahid lari ke Kuala Lumpur, tidak sanggup melihat memori silam ayahnya. Ilyasa pula terasa hati kerana rasa abangnya sudah tidak pedulikan keluarga.\r\nTinggallah Hanani keseorangan; tersepit antara marah dan rindu. Hanya ibu tempat dia mengadu.\r\nApabila kesihatan ibu semakin merosot ketika bulan puasa, Hanani mengumpul semangat. Kali ini, dia mesti bawa abangnya pulang. Abang, mak dah tak ada masa!\r\nTetapi ketika Wahid akhirnya pulang, ibu yang paling gembira dapat beraya bersama kesemua anaknya tiba-tiba menghilang.\r\nMampukah Wahid, Hanani dan Ilyasa menggunakan deria istimewa mereka dan mengembalikan semula kenangan lama demi mencari insan tersayang? Adakah sengketa dapat diketepikan sebelum benar-benar terlambat?', 'uploads/covers/6a3ded1d5895ee7601d1f052570f6b28.jpg', 0, '2026-09-30 17:51:18');

-- --------------------------------------------------------

--
-- Table structure for table `book_clubs`
--

CREATE TABLE `book_clubs` (
  `club_id` int(11) NOT NULL,
  `club_name` varchar(100) NOT NULL,
  `genre` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `min_age` tinyint(3) UNSIGNED NOT NULL DEFAULT 13,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book_clubs`
--

INSERT INTO `book_clubs` (`club_id`, `club_name`, `genre`, `description`, `created_by`, `created_at`, `min_age`, `status`) VALUES
(1, 'Syaff Book Club', 'Fantasy', 'gathered to all fantasy lover.\r\n\r\n#welovedragon', 1, '2026-09-26 18:49:04', 16, 'approved'),
(2, 'Syaff Book Club', 'Fantasy', 'To gathered all the fantasy lover\r\n\r\n#welovedragon', 1, '2026-09-26 19:10:00', 16, 'rejected'),
(3, 'Games of Tomes', 'Fantasy', 'Have members draw their own quick fantasy maps of the book\'s fictional world or invent a custom magic system before diving into the discussion.', 1, '2026-09-29 16:09:17', 13, 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `book_requests`
--

CREATE TABLE `book_requests` (
  `request_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `author` varchar(150) NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `status` enum('pending','added','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `book_requests`
--

INSERT INTO `book_requests` (`request_id`, `user_id`, `title`, `author`, `reason`, `status`, `created_at`, `reviewed_at`, `reviewed_by`) VALUES
(1, 1, 'Si Bakhil dengan Bunganya', 'Liza Nur', 'The book is marveolous i need it here for everyone to read and enjoy', 'added', '2026-09-29 18:07:59', '2026-09-29 18:08:06', 1),
(2, 2, 'Syurga itu Bernama Ibu', 'Osman Deen', 'Masa bersama ibu terasa begitu panjang, sehingga kita menyangka dia akan sentiasa ada. Tetapi apabila dia pergi, barulah kita sedar bahawa masa yang panjang itu sebenarnya hanya sekejap. Yang tinggal hanyalah kenangan dan rindu yang tidak pernah habis.Jadi saya nak semua user mengetahui there\'s a such emotional book to menghargai our mother', 'added', '2026-09-29 18:12:52', '2026-09-30 17:51:36', 1),
(3, 10, 'A Song of Ice and Fire', 'George R.R Martin', 'Really appreciate if admin could bring this book here for everyone else loving this series as hard as i do!', 'pending', '2026-09-30 17:43:41', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `club_memberships`
--

CREATE TABLE `club_memberships` (
  `club_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `club_memberships`
--

INSERT INTO `club_memberships` (`club_id`, `user_id`, `joined_at`) VALUES
(1, 1, '2026-09-26 20:11:31'),
(1, 10, '2026-09-30 17:39:11'),
(3, 2, '2026-09-29 17:03:15'),
(3, 6, '2026-09-30 17:45:24'),
(3, 9, '2026-09-30 19:02:11');

-- --------------------------------------------------------

--
-- Table structure for table `club_posts`
--

CREATE TABLE `club_posts` (
  `post_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_text` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `club_posts`
--

INSERT INTO `club_posts` (`post_id`, `club_id`, `user_id`, `post_text`, `status`, `created_at`) VALUES
(1, 1, 1, 'im currently read love hypothesis and its so goood. I love the chemistry between the character, I love the story telling, the plot its Goooooood', 'approved', '2026-09-26 19:58:11'),
(2, 1, 1, 'busuk lah club ni', 'rejected', '2026-09-27 17:05:57'),
(3, 1, 10, 'excited to be in this club!!', 'approved', '2026-09-30 17:40:01');

-- --------------------------------------------------------

--
-- Table structure for table `club_post_comments`
--

CREATE TABLE `club_post_comments` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `club_post_comments`
--

INSERT INTO `club_post_comments` (`comment_id`, `post_id`, `user_id`, `comment_text`, `status`, `created_at`) VALUES
(1, 1, 1, 'yesssss so related', 'approved', '2026-09-26 20:50:56'),
(2, 1, 1, 'yer betul', 'rejected', '2026-09-26 21:13:32');

-- --------------------------------------------------------

--
-- Table structure for table `club_post_likes`
--

CREATE TABLE `club_post_likes` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `club_post_likes`
--

INSERT INTO `club_post_likes` (`post_id`, `user_id`, `created_at`) VALUES
(1, 1, '2026-09-27 15:40:22');

-- --------------------------------------------------------

--
-- Table structure for table `finished_books`
--

CREATE TABLE `finished_books` (
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `finished_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finished_books`
--

INSERT INTO `finished_books` (`user_id`, `book_id`, `finished_at`) VALUES
(1, 4, '2026-09-29'),
(1, 5, '2026-09-29'),
(1, 6, '2026-09-29'),
(10, 13, '2026-10-01');

-- --------------------------------------------------------

--
-- Table structure for table `reading_goals`
--

CREATE TABLE `reading_goals` (
  `user_id` int(11) NOT NULL,
  `goal_year` int(11) NOT NULL,
  `target_books` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reading_goals`
--

INSERT INTO `reading_goals` (`user_id`, `goal_year`, `target_books`) VALUES
(1, 2026, 10),
(10, 2026, 1);

-- --------------------------------------------------------

--
-- Table structure for table `reading_list`
--

CREATE TABLE `reading_list` (
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `status` enum('reading','want_to_read') NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reading_list`
--

INSERT INTO `reading_list` (`user_id`, `book_id`, `status`, `added_at`) VALUES
(6, 3, 'reading', '2026-09-29 16:07:13'),
(10, 10, 'want_to_read', '2026-09-30 17:36:24');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `book_id`, `user_id`, `rating`, `review_text`, `created_at`, `status`) VALUES
(3, 3, 6, 5, 'hihi suka plot dia!!!', '2026-09-29 16:07:04', 'approved'),
(4, 13, 10, 5, 'Look at me falling in love with this book, never though it would happen but here i am!!!!!!', '2026-09-30 17:38:41', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `birth_date` date NOT NULL,
  `is_minor` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('member','moderator') NOT NULL DEFAULT 'member'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `birth_date`, `is_minor`, `created_at`, `role`) VALUES
(1, 'Shaff', 'hazw4ni05@gmail.com', '$2y$10$YVDqnrWtmFtgJMs4gUw0/OXFbqIfas0YVXEaNFTW7DwzSjixqnQgO', '2006-10-05', 0, '2026-09-26 17:12:14', 'moderator'),
(2, 'dinz', 'dinz23@gmail.com', '$2y$10$LHeTwb51Oz6dxnKZcKkzCu7On4DyeEEVpDKglxq1EiZb.KyxZBhfW', '2006-07-31', 0, '2026-09-27 16:55:21', 'member'),
(6, 'zainab', 'KL2412018265@student.uptm.edu.my', '$2y$10$imDyR4oKe62HCAOksAsJ/Oy1QZva1//C.yMFvJtlY3IwI/9lv7kyG', '2012-10-05', 1, '2026-09-29 15:54:03', 'member'),
(9, 'irma', 'irma@gmail.com', '$2y$10$sVmuCVeibSdyfvjjLz1taOsQUsGaCJhBd5VJa/a3E/X7qjEN41oBm', '2010-12-12', 1, '2026-09-29 15:59:29', 'member'),
(10, 'ahmad', 'ahmad05@gmail.com', '$2y$10$kovZDrPdW9AWgN5bLMxO2uNR2ldCTKe28vXt0cLPBGeMF5jHvW5yq', '2005-05-05', 0, '2026-09-30 17:35:39', 'member');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`);

--
-- Indexes for table `book_clubs`
--
ALTER TABLE `book_clubs`
  ADD PRIMARY KEY (`club_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `book_requests`
--
ALTER TABLE `book_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `idx_status_created` (`status`,`created_at`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`),
  ADD KEY `fk_book_requests_moderator` (`reviewed_by`);

--
-- Indexes for table `club_memberships`
--
ALTER TABLE `club_memberships`
  ADD PRIMARY KEY (`club_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `club_posts`
--
ALTER TABLE `club_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `club_id` (`club_id`,`status`,`created_at`),
  ADD KEY `fk_club_posts_user` (`user_id`);

--
-- Indexes for table `club_post_comments`
--
ALTER TABLE `club_post_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `post_id` (`post_id`,`status`,`created_at`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `club_post_likes`
--
ALTER TABLE `club_post_likes`
  ADD PRIMARY KEY (`post_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `finished_books`
--
ALTER TABLE `finished_books`
  ADD PRIMARY KEY (`user_id`,`book_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `reading_goals`
--
ALTER TABLE `reading_goals`
  ADD PRIMARY KEY (`user_id`,`goal_year`);

--
-- Indexes for table `reading_list`
--
ALTER TABLE `reading_list`
  ADD PRIMARY KEY (`user_id`,`book_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `book_clubs`
--
ALTER TABLE `book_clubs`
  MODIFY `club_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `book_requests`
--
ALTER TABLE `book_requests`
  MODIFY `request_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `club_posts`
--
ALTER TABLE `club_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `club_post_comments`
--
ALTER TABLE `club_post_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `book_clubs`
--
ALTER TABLE `book_clubs`
  ADD CONSTRAINT `book_clubs_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `book_requests`
--
ALTER TABLE `book_requests`
  ADD CONSTRAINT `fk_book_requests_moderator` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_book_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `club_memberships`
--
ALTER TABLE `club_memberships`
  ADD CONSTRAINT `club_memberships_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `book_clubs` (`club_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `club_memberships_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `club_posts`
--
ALTER TABLE `club_posts`
  ADD CONSTRAINT `fk_club_posts_club` FOREIGN KEY (`club_id`) REFERENCES `book_clubs` (`club_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_club_posts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `club_post_comments`
--
ALTER TABLE `club_post_comments`
  ADD CONSTRAINT `club_post_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `club_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `club_post_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `club_post_likes`
--
ALTER TABLE `club_post_likes`
  ADD CONSTRAINT `club_post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `club_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `club_post_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `finished_books`
--
ALTER TABLE `finished_books`
  ADD CONSTRAINT `finished_books_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `finished_books_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE;

--
-- Constraints for table `reading_goals`
--
ALTER TABLE `reading_goals`
  ADD CONSTRAINT `reading_goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
