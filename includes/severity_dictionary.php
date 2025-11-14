<?php
/**
 * severity_dictionary.php
 * 
 * Comprehensive bullying keyword dictionary for SafeSpeak severity detection.
 * Sorted by points:
 * - Critical Keywords: 10 points
 * - High Keywords: 5 points
 * - Medium Keywords: 2 points
 * 
 */

return [

    // ---------------------------
    // CRITICAL KEYWORDS (6 pts)
    // ---------------------------
    'critical' => [

        // Physical Bullying
        "weapon","gun","knife","stab","serious injury","blood","death","kill","murdered",
        "sandata","baril","kutsilyo","pusil","saksak","hilabtan","panggabugsa","matinding sakit",
        "pumatay","pagpatay","gi hilabtan","gi saksak","gi pusil","gi panghipo kusog",

        // Sexual Bullying
        "rape","attempted rape","sexual assault","sexually assaulted","molested",
        "hinipuan","hinihipuan","panghipo","pinaghalik na di ko gusto",
        "forced sex","pinilit makipagtalik","pina-hubad","pinakuwestiyon ang katawan",
        "sexual harassment","ginahubaran ako","ginapanghipo ako","sexual coercion",
        "forced kissing","pinilit humalik","gi hipos ko","gi hilabtan ko","gi hulbot ko",
        "gi pilit ko sa kama","ginahubad ang damit ko","gi expose ko sa sexual",
        "pinapakita ang ari","pina touch ko","gi kuha ko ang pribadong parte",
        "gi tangtang ang damit","gi panghipo ko kusog","gi panghilabtan ko",
        "gi hulbot sa dako","gi panghipo ko sa likod","gi pakauwaw ko sexual",
        "gi panglandian","ginahubaran ang bata","sexual abuse","sexual molestation",
        "sexual violence","hinubaran","gi kuskit ko","gi silotan ko sexual","gi puyo ko",
        "gi pusil sa kama","gi pigilan sexual","gi pwersa sexual","sexual exploitation",
        "sexual threats","ginatarget ako sexual",

        // Cyberbullying
        "doxxed","doxxing","private info exposed","gi leak ang info","account hacked",
        "gi hack ang account","gi hack ko","gi threaten online","blackmail","gi pamugos online",
        "gi pang blackmail ko","gi post private info","gi post sa social","gi upload private",
        "gi share sensitive info","gi expose","gi post confidential","gi stalk online",
        "gi track ko","gi follow ko online","gi pang cyber harassment","gi send threatening messages",
        "gi padala threat","gi manipulate ko online","gi insult ko online","gi troll ko heavily",
        "gi cyber attack ko","gi post ko sa grupo without consent","gi share ang nude",
        "gi tag sa embarrassing photo","gi spread private content","gi fake account ko",
        "impersonated me online","gi lure online","gi harass online","gi pang send threats",
        "gi email threats","gi text threatening","gi chat threats","gi insult publicly",
        "gi post harmful meme",

        // Prejudicial Bullying
        "racist attack","gi insult ko based sa race","ethnic slur","gi tawag ko bastos na lahi",
        "religious attack","gi insult ko based sa religion","gi pang insult faith",
        "gender-based violence","gi insult ko based sa gender","gi diskrimina ko",
        "gi target ko based sa sexual orientation","homophobic attack","gi insult ko LGBTQ",
        "gi pang insult sa minority","gi harass ko based sa color","gi insult sa national origin",
        "gi panghatag insult sa culture","gi pangdaot ko based sa language","gi exclude ko based sa lahi",
        "gi block based sa identity","gi humiliate ko publicly","gi threaten ko based sa identity",
        "gi post discriminatory content","gi spread hate","gi pang post offensive content",
        "gi pang target minority","gi insult family background","gi ridicule ko based sa origin",
        "gi pang tawag derogatory","gi harass emotionally based sa race","gi insult ko sa skin color",

        // Verbal Bullying
        "threatened to kill","gi bantaan ko patayin","gi panakot ko","kill me","pumatay ko",
        "gi insult ko malala","humiliated publicly","gi pahubad ko words","gi siraan ko pangalan",
        "gi pangmata ko","gi stigmatize ko","gi insult ko sa harap ng lahat","gi shout sa face",
        "gi berbal na abuse","gi tawag ko bastos","gi tawag ko tanga","gi tawag ko gago",
        "gi tawag ko inutil","gi tawag ko idiot","gi tawag ko worthless","gi insult family ko",
        "gi insult home ko","gi insult tribe ko","gi insult ethnic group ko","gi insult faith ko",
        "gi threaten emotionally","gi manipulate verbally","gi intimidate","gi berate ko harshly",
        "gi degrade ko","gi ridicule publicly"
    ],

    // ---------------------------
    // HIGH KEYWORDS (4 pts)
    // ---------------------------
    'high' => [

        // Physical Bullying
        "threat","assault","attack","hurt","beat","beaten","punch","kick","choke","stalk",
        "harass","threaten","injury","injure","hit","slap","banta","pag-atake","sakamay",
        "sakasama","palo","babaguhan","muki","takot","paghabol","abaluhan","tangkad","hampas",
        "damgo","igka-sakop","lapok","babag","pumyada","takod","atubang","abuso","hisak",

        // Sexual Bullying
        "catcall","flirted aggressively","gi follow ko sexually","sexual comment","pinagusapan ako sexual",
        "pinagusapan bastos","gi text ko sexual","sexual jokes","pinagtripan sexual","sexual remark",
        "gi komentohan","flirted without consent","gi hipos ko lightly","gi hilabtan lightly",
        "gi tilaw ko sexual","gi pang landian gamay","pinapansin sexually","sexual teasing",
        "sexual bullying","gi follow sexually","gi stalk ko sexual","gi mensahe ko bastos","gi tawagan ko sexual",
        "sexual intimidation","sexual threat","gi expose ko gamay","gi comment ko sexual","sexual prank",
        "sexual mockery","gi patuyok ko sexual","sexual harassment in chat","gi tag ko sexual",
        "gi post ko sexual","gi share ko sexual content","sexual meme","gi pakita ko sexual","gi send ko sexual image",

        // Cyberbullying
        "gi mock online","gi ridicule ko","gi insult sa chat","gi comment ko negative","gi tag embarrassing",
        "gi joke ko online","gi tease online","gi criticize publicly","gi spread rumors online",
        "gi share lies","gi call out online","gi ridicule publicly","gi post funny but hurtful",
        "gi meme ko","gi GIF ko mocking","gi video ko mocking","gi screenshot chat","gi share chat",
        "gi chat ko embarrassing","gi ridicule sa grupo","gi spam ko","gi troll ko","gi mention ko",
        "gi tag ko joke","gi tease sa messenger","gi ignore ko sa group","gi exclude sa online",
        "gi block ko online","gi prank ko online","gi trick online","gi humiliate ko online",
        "gi manipulate ko sa chat","gi insult privately","gi ridicule privately","gi call out privately",
        "gi shame publicly",

        // Prejudicial Bullying
        "gi insult ko based sa appearance","gi mock based sa lahi","gi ridicule based sa religion",
        "gi tease based sa gender","gi joke ko based sa identity","gi comment offensive",
        "gi share offensive meme","gi tag ko derogatory","gi call out based sa minority",
        "gi ignore ko based sa identity","gi exclude ko from group based sa culture","gi joke ko sa skin color",
        "gi insult sa accent","gi ridicule ko sa language","gi mock ko sa clothing","gi tease sa culture",
        "gi criticize based sa gender","gi comment negative sa sexual orientation","gi ridicule lightly based sa race",
        "gi call out jokingly based sa religion","gi poke derogatory","gi mention ko insulting",
        "gi post ko joke derogatory","gi prank based sa identity","gi joke lightly sa minority",
        "gi tease jokingly based sa ethnicity","gi insult lightly sa faith","gi ridicule lightly sa appearance",

        // Verbal Bullying
        "gi insult ko","gi tawag ko fool","gi tawag ko baka","gi tawag ko bad","gi tawag ko stupid",
        "gi ridicule ko","gi mock ko","gi tease ko","gi joke ko harshly","gi comment negative",
        "gi berate ko","gi shout ko","gi yell ko","gi humiliate ko lightly","gi criticize ko",
        "gi nagalit ko sa face","gi panlalait ko","gi patawa ko insult","gi poke ko verbally",
        "gi mention ko insult","gi ridicule ko publicly","gi joke ko publicly","gi comment ko publicly",
        "gi nagbastos ko","gi pinagtawanan ko","gi pasakit ko words","gi insult lightly",
        "gi criticize publicly","gi tease publicly","gi berate publicly","gi humiliate lightly"
    ],

    // ---------------------------
    // MEDIUM KEYWORDS (2 pts)
    // ---------------------------
    'medium' => [

        // Physical Bullying
        "bully","bullying","exclusion","rumor","name-call","mock","tease","embarrass","humiliate","shame",
        "pag-bully","lumabas","tsismis","pangatawaran","alibog","nakakaaliw","mapahiya","humuhubad",
        "pagpapahiya","hiya","sira-ngalan","pagpili","balita","pulongan","chikita","tukso","pagahon",
        "atipan","kahoy","hiya","sura",

        // Sexual Bullying
        "flirted","winked at me","gi smile sexual","gi joke sexual","sexual teasing","sexual comment light",
        "sexual joke","sexual rumor","gi tag ko joke sexual","pinagtripan joke sexual",
        "gi text ko joke sexual","gi tawag ko babe","gi tawag ko cute","sexual compliment",
        "sexual nickname","gi comment ko joke","sexual reference","sexual innuendo","pinagtripan sexual lightly",
        "sexual gesture","gi hipos lightly","gi hilabtan lightly","gi tilaw lightly","sexual meme lightly",
        "sexual post joke","sexual prank lightly","gi send lightly","sexual photo joke","sexual message joke",
        "sexual whisper joke","sexual teasing light","gi patawa sexual","sexual mocking",

        // Cyberbullying
        "gi joke ko lightly","gi tease lightly","gi comment ko funny","gi poke ko online","gi tag joke",
        "gi ignore ko lightly","gi block ko temporarily","gi prank ko lightly","gi mention ko lightly",
        "gi post ko harmless","gi meme lightly","gi GIF lightly","gi video lightly","gi nickname joke",
        "gi insult joke","gi tease sa messenger lightly","gi ridicule sa chat lightly",
        "gi tease sa group lightly","gi call out lightly","gi mock lightly","gi joke sa profile",
        "gi prank sa social","gi poke lightly","gi mention jokingly","gi tag harmless","gi comment lightly",
        "gi reply lightly","gi post harmlessly","gi spam lightly","gi troll lightly","gi ignore lightly",

        // Prejudicial Bullying
        "gi joke lightly sa race","gi tease lightly sa gender","gi comment lightly sa religion",
        "gi mock lightly sa identity","gi poke lightly sa minority","gi nickname lightly derogatory",
        "gi prank lightly based sa culture","gi tag lightly sa joke","gi mention lightly sa faith",
        "gi tease lightly sa appearance","gi poke lightly sa clothing","gi joke lightly sa accent",
        "gi comment lightly sa sexual orientation","gi ridicule lightly sa language","gi mock lightly sa skin color",
        "gi tease jokingly","gi joke sa harmless way","gi call out lightly","gi post harmless",
        "gi meme harmlessly","gi GIF harmlessly","gi share joke harmless","gi comment joke harmless",
        "gi mention joke harmless","gi nickname harmless","gi prank harmlessly","gi tag harmless",

        // Verbal Bullying
        "gi joke ko lightly","gi tease ko lightly","gi poke ko lightly","gi comment ko lightly",
        "gi mention ko lightly","gi ridicule lightly","gi mock lightly","gi call out lightly",
        "gi nickname lightly","gi prank lightly","gi poke jokingly","gi tease jokingly",
        "gi joke harmlessly","gi comment harmlessly","gi meme lightly","gi GIF lightly",
        "gi post harmlessly","gi share lightly","gi tag lightly","gi mention jokingly",
        "gi nickname harmlessly","gi prank harmlessly","gi poke harmlessly","gi tease harmlessly",
        "gi call out harmlessly","gi joke in group lightly","gi comment in chat lightly",
        "gi mention harmlessly","gi poke harmlessly online","gi tease harmlessly online","gi meme harmlessly online"
    ]
];
