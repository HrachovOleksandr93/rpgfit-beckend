# Activity & Workout Type Mapping + RPG Professions

> Source of truth for mapping Flutter `health` package `HealthWorkoutActivityType` enums
> to native iOS (HealthKit `HKWorkoutActivityType`) and Android (Health Connect `ExerciseSessionRecord.EXERCISE_TYPE_*`) types,
> organized into RPG profession categories.
>
> Generated for database seeder use.

---

## Table of Contents

1. [Legend](#legend)
2. [Activity Mapping Table](#activity-mapping-table)
   - [Group 1: Universal (both iOS and Android)](#group-1-universal-both-ios-and-android)
   - [Group 2: iOS Only (with Android fallback)](#group-2-ios-only-with-android-fallback)
   - [Group 3: Android Only (with iOS fallback)](#group-3-android-only-with-ios-fallback)
3. [RPG Professions per Category](#rpg-professions-per-category)

---

## Legend

| Column | Description |
|--------|-------------|
| `slug` | Unique snake_case identifier used in the database |
| `display_name` | Human-readable name shown in the app UI |
| `flutter_enum` | Exact `HealthWorkoutActivityType` enum value from the Flutter `health` package |
| `ios_native` | Apple HealthKit `HKWorkoutActivityType` case name |
| `android_native` | Android Health Connect `ExerciseSessionRecord.EXERCISE_TYPE_*` constant |
| `fallback_slug` | For platform-specific types: the slug of the closest equivalent on the other platform |
| `category` | RPG category grouping (see categories below) |

Platform key:
- **Universal** = mapped natively on both iOS and Android
- **iOS only** = exists in HealthKit but not Health Connect; `android_native` shows `N/A`, `fallback_slug` points to an Android-available type
- **Android only** = exists in Health Connect but not HealthKit; `ios_native` shows `N/A`, `fallback_slug` points to an iOS-available type

---

## Activity Mapping Table

### Group 1: Universal (both iOS and Android)

These types have native support on both platforms. No fallback needed.

| slug | display_name | flutter_enum | ios_native | android_native | fallback_slug | category |
|------|-------------|-------------|-----------|---------------|--------------|----------|
| `american_football` | American Football | AMERICAN_FOOTBALL | americanFootball | EXERCISE_TYPE_FOOTBALL_AMERICAN | - | team_sports |
| `archery` | Archery | ARCHERY | archery | EXERCISE_TYPE_ARCHERY | - | outdoor |
| `australian_football` | Australian Football | AUSTRALIAN_FOOTBALL | australianFootball | EXERCISE_TYPE_FOOTBALL_AUSTRALIAN | - | team_sports |
| `badminton` | Badminton | BADMINTON | badminton | EXERCISE_TYPE_BADMINTON | - | racquet_sports |
| `baseball` | Baseball | BASEBALL | baseball | EXERCISE_TYPE_BASEBALL | - | team_sports |
| `basketball` | Basketball | BASKETBALL | basketball | EXERCISE_TYPE_BASKETBALL | - | team_sports |
| `biking` | Biking | BIKING | cycling | EXERCISE_TYPE_BIKING | - | cycling |
| `boxing` | Boxing | BOXING | boxing | EXERCISE_TYPE_BOXING | - | combat |
| `cricket` | Cricket | CRICKET | cricket | EXERCISE_TYPE_CRICKET | - | team_sports |
| `cross_country_skiing` | Cross-Country Skiing | CROSS_COUNTRY_SKIING | crossCountrySkiing | EXERCISE_TYPE_CROSS_COUNTRY_SKIING | - | winter_sports |
| `elliptical` | Elliptical | ELLIPTICAL | elliptical | EXERCISE_TYPE_ELLIPTICAL | - | cardio |
| `fencing` | Fencing | FENCING | fencing | EXERCISE_TYPE_FENCING | - | combat |
| `golf` | Golf | GOLF | golf | EXERCISE_TYPE_GOLF | - | outdoor |
| `gymnastics` | Gymnastics | GYMNASTICS | gymnastics | EXERCISE_TYPE_GYMNASTICS | - | flexibility |
| `handball` | Handball | HANDBALL | handball | EXERCISE_TYPE_HANDBALL | - | team_sports |
| `high_intensity_interval_training` | HIIT | HIGH_INTENSITY_INTERVAL_TRAINING | highIntensityIntervalTraining | EXERCISE_TYPE_HIGH_INTENSITY_INTERVAL_TRAINING | - | cardio |
| `hiking` | Hiking | HIKING | hiking | EXERCISE_TYPE_HIKING | - | walking |
| `hockey` | Hockey | HOCKEY | hockey | EXERCISE_TYPE_ICE_HOCKEY | - | team_sports |
| `jump_rope` | Jump Rope | JUMP_ROPE | jumpRope | EXERCISE_TYPE_JUMPING_ROPE | - | cardio |
| `kickboxing` | Kickboxing | KICKBOXING | kickboxing | EXERCISE_TYPE_KICKBOXING | - | combat |
| `martial_arts` | Martial Arts | MARTIAL_ARTS | martialArts | EXERCISE_TYPE_MARTIAL_ARTS | - | combat |
| `pilates` | Pilates | PILATES | pilates | EXERCISE_TYPE_PILATES | - | flexibility |
| `racquetball` | Racquetball | RACQUETBALL | racquetball | EXERCISE_TYPE_RACQUETBALL | - | racquet_sports |
| `rowing` | Rowing | ROWING | rowing | EXERCISE_TYPE_ROWING | - | water_sports |
| `rugby` | Rugby | RUGBY | rugby | EXERCISE_TYPE_RUGBY | - | team_sports |
| `running` | Running | RUNNING | running | EXERCISE_TYPE_RUNNING | - | running |
| `sailing` | Sailing | SAILING | sailing | EXERCISE_TYPE_SAILING | - | water_sports |
| `skating` | Skating | SKATING | skating | EXERCISE_TYPE_SKATING | - | winter_sports |
| `snowboarding` | Snowboarding | SNOWBOARDING | snowboarding | EXERCISE_TYPE_SNOWBOARDING | - | winter_sports |
| `soccer` | Soccer | SOCCER | soccer | EXERCISE_TYPE_SOCCER | - | team_sports |
| `softball` | Softball | SOFTBALL | softball | EXERCISE_TYPE_SOFTBALL | - | team_sports |
| `squash` | Squash | SQUASH | squash | EXERCISE_TYPE_SQUASH | - | racquet_sports |
| `stair_climbing` | Stair Climbing | STAIR_CLIMBING | stairClimbing | EXERCISE_TYPE_STAIR_CLIMBING | - | walking |
| `swimming` | Swimming | SWIMMING | swimming | EXERCISE_TYPE_SWIMMING | - | swimming |
| `table_tennis` | Table Tennis | TABLE_TENNIS | tableTennis | EXERCISE_TYPE_TABLE_TENNIS | - | racquet_sports |
| `tennis` | Tennis | TENNIS | tennis | EXERCISE_TYPE_TENNIS | - | racquet_sports |
| `volleyball` | Volleyball | VOLLEYBALL | volleyball | EXERCISE_TYPE_VOLLEYBALL | - | team_sports |
| `walking` | Walking | WALKING | walking | EXERCISE_TYPE_WALKING | - | walking |
| `water_polo` | Water Polo | WATER_POLO | waterPolo | EXERCISE_TYPE_WATER_POLO | - | swimming |
| `yoga` | Yoga | YOGA | yoga | EXERCISE_TYPE_YOGA | - | flexibility |
| `bowling` | Bowling | BOWLING | bowling | EXERCISE_TYPE_BOWLING | - | other |
| `climbing` | Climbing | CLIMBING | climbing | EXERCISE_TYPE_ROCK_CLIMBING | - | outdoor |
| `wrestling` | Wrestling | WRESTLING | wrestling | EXERCISE_TYPE_WRESTLING | - | combat |
| `surfing` | Surfing | SURFING | surfingSports | EXERCISE_TYPE_SURFING | - | water_sports |
| `tai_chi` | Tai Chi | TAI_CHI | taiChi | EXERCISE_TYPE_TAI_CHI | - | flexibility |
| `dancing` | Dancing | DANCING | dance | EXERCISE_TYPE_DANCING | - | dance |
| `downhill_skiing` | Downhill Skiing | DOWNHILL_SKIING | downhillSkiing | EXERCISE_TYPE_SKIING | - | winter_sports |
| `other` | Other | OTHER | other | EXERCISE_TYPE_OTHER_WORKOUT | - | other |

---

### Group 2: iOS Only (with Android fallback)

These types exist natively in Apple HealthKit but have no direct equivalent in Android Health Connect. The `fallback_slug` indicates the closest Android-supported type.

| slug | display_name | flutter_enum | ios_native | android_native | fallback_slug | category |
|------|-------------|-------------|-----------|---------------|--------------|----------|
| `barre` | Barre | BARRE | barre | N/A | `pilates` | flexibility |
| `cardio_dance` | Cardio Dance | CARDIO_DANCE | cardioDance | N/A | `dancing` | dance |
| `cooldown` | Cooldown | COOLDOWN | cooldown | N/A | `other` | mind_body |
| `core_training` | Core Training | CORE_TRAINING | coreTraining | N/A | `strength_training` | strength |
| `cross_training` | Cross Training | CROSS_TRAINING | crossTraining | N/A | `high_intensity_interval_training` | cardio |
| `curling` | Curling | CURLING | curling | N/A | `other` | winter_sports |
| `disc_sports` | Disc Sports | DISC_SPORTS | discSports | N/A | `other` | other |
| `equestrian_sports` | Equestrian Sports | EQUESTRIAN_SPORTS | equestrianSports | N/A | `other` | outdoor |
| `fishing` | Fishing | FISHING | fishing | N/A | `other` | outdoor |
| `fitness_gaming` | Fitness Gaming | FITNESS_GAMING | fitnessGaming | N/A | `other` | other |
| `flexibility` | Flexibility | FLEXIBILITY | flexibility | N/A | `yoga` | flexibility |
| `functional_strength_training` | Functional Strength Training | FUNCTIONAL_STRENGTH_TRAINING | functionalStrengthTraining | N/A | `strength_training` | strength |
| `hand_cycling` | Hand Cycling | HAND_CYCLING | handCycling | N/A | `biking` | cycling |
| `hunting` | Hunting | HUNTING | hunting | N/A | `hiking` | outdoor |
| `lacrosse` | Lacrosse | LACROSSE | lacrosse | N/A | `other` | team_sports |
| `mind_and_body` | Mind and Body | MIND_AND_BODY | mindAndBody | N/A | `yoga` | mind_body |
| `mixed_cardio` | Mixed Cardio | MIXED_CARDIO | mixedCardio | N/A | `high_intensity_interval_training` | cardio |
| `paddle_sports` | Paddle Sports | PADDLE_SPORTS | paddleSports | N/A | `rowing` | water_sports |
| `pickleball` | Pickleball | PICKLEBALL | pickleball | N/A | `badminton` | racquet_sports |
| `play` | Play | PLAY | play | N/A | `other` | other |
| `preparation_and_recovery` | Preparation and Recovery | PREPARATION_AND_RECOVERY | preparationAndRecovery | N/A | `other` | mind_body |
| `snow_sports` | Snow Sports | SNOW_SPORTS | snowSports | N/A | `downhill_skiing` | winter_sports |
| `social_dance` | Social Dance | SOCIAL_DANCE | socialDance | N/A | `dancing` | dance |
| `stairs` | Stairs | STAIRS | stairs | N/A | `stair_climbing` | walking |
| `step_training` | Step Training | STEP_TRAINING | stepTraining | N/A | `high_intensity_interval_training` | cardio |
| `track_and_field` | Track and Field | TRACK_AND_FIELD | trackAndField | N/A | `running` | running |
| `traditional_strength_training` | Traditional Strength Training | TRADITIONAL_STRENGTH_TRAINING | traditionalStrengthTraining | N/A | `strength_training` | strength |
| `underwater_diving` | Underwater Diving | UNDERWATER_DIVING | underwaterDiving | N/A | `scuba_diving` | water_sports |
| `water_fitness` | Water Fitness | WATER_FITNESS | waterFitness | N/A | `swimming` | swimming |
| `water_sports` | Water Sports | WATER_SPORTS | waterSports | N/A | `swimming` | water_sports |
| `wheelchair_run_pace` | Wheelchair Run Pace | WHEELCHAIR_RUN_PACE | wheelchairRunPace | N/A | `wheelchair` | other |
| `wheelchair_walk_pace` | Wheelchair Walk Pace | WHEELCHAIR_WALK_PACE | wheelchairWalkPace | N/A | `wheelchair` | other |

---

### Group 3: Android Only (with iOS fallback)

These types exist natively in Android Health Connect but have no direct equivalent in Apple HealthKit. The `fallback_slug` indicates the closest iOS-supported type.

| slug | display_name | flutter_enum | ios_native | android_native | fallback_slug | category |
|------|-------------|-------------|-----------|---------------|--------------|----------|
| `biking_stationary` | Stationary Biking | BIKING_STATIONARY | N/A | EXERCISE_TYPE_BIKING_STATIONARY | `biking` | cycling |
| `calisthenics` | Calisthenics | CALISTHENICS | N/A | EXERCISE_TYPE_CALISTHENICS | `functional_strength_training` | strength |
| `frisbee_disc` | Frisbee Disc | FRISBEE_DISC | N/A | EXERCISE_TYPE_FRISBEE_DISC | `disc_sports` | other |
| `guided_breathing` | Guided Breathing | GUIDED_BREATHING | N/A | EXERCISE_TYPE_GUIDED_BREATHING | `mind_and_body` | mind_body |
| `ice_skating` | Ice Skating | ICE_SKATING | N/A | EXERCISE_TYPE_ICE_SKATING | `skating` | winter_sports |
| `paragliding` | Paragliding | PARAGLIDING | N/A | EXERCISE_TYPE_PARAGLIDING | `other` | outdoor |
| `rock_climbing` | Rock Climbing | ROCK_CLIMBING | N/A | EXERCISE_TYPE_ROCK_CLIMBING | `climbing` | outdoor |
| `rowing_machine` | Rowing Machine | ROWING_MACHINE | N/A | EXERCISE_TYPE_ROWING_MACHINE | `rowing` | water_sports |
| `running_treadmill` | Treadmill Running | RUNNING_TREADMILL | N/A | EXERCISE_TYPE_RUNNING_TREADMILL | `running` | running |
| `scuba_diving` | Scuba Diving | SCUBA_DIVING | N/A | EXERCISE_TYPE_SCUBA_DIVING | `underwater_diving` | water_sports |
| `skiing` | Skiing | SKIING | N/A | EXERCISE_TYPE_SKIING | `downhill_skiing` | winter_sports |
| `snowshoeing` | Snowshoeing | SNOWSHOEING | N/A | EXERCISE_TYPE_SNOWSHOEING | `hiking` | winter_sports |
| `stair_climbing_machine` | Stair Climbing Machine | STAIR_CLIMBING_MACHINE | N/A | EXERCISE_TYPE_STAIR_CLIMBING_MACHINE | `stair_climbing` | walking |
| `strength_training` | Strength Training | STRENGTH_TRAINING | N/A | EXERCISE_TYPE_STRENGTH_TRAINING | `traditional_strength_training` | strength |
| `swimming_open_water` | Open Water Swimming | SWIMMING_OPEN_WATER | N/A | EXERCISE_TYPE_SWIMMING_OPEN_WATER | `swimming` | swimming |
| `swimming_pool` | Pool Swimming | SWIMMING_POOL | N/A | EXERCISE_TYPE_SWIMMING_POOL | `swimming` | swimming |
| `walking_treadmill` | Treadmill Walking | WALKING_TREADMILL | N/A | EXERCISE_TYPE_WALKING_TREADMILL | `walking` | walking |
| `weightlifting` | Weightlifting | WEIGHTLIFTING | N/A | EXERCISE_TYPE_WEIGHTLIFTING | `traditional_strength_training` | strength |
| `wheelchair` | Wheelchair | WHEELCHAIR | N/A | EXERCISE_TYPE_WHEELCHAIR | `wheelchair_walk_pace` | other |

---

## Summary Statistics

| Group | Count |
|-------|-------|
| Universal (both platforms) | 47 |
| iOS only | 32 |
| Android only | 19 |
| **Total** | **98** |
| + OTHER | 1 |
| **Grand Total** | **99** |

### Activities per Category

| Category | Count |
|----------|-------|
| combat | 6 |
| running | 3 |
| walking | 6 |
| cycling | 3 |
| swimming | 5 |
| strength | 7 |
| flexibility | 6 |
| cardio | 6 |
| dance | 4 |
| winter_sports | 10 |
| racquet_sports | 6 |
| team_sports | 13 |
| water_sports | 8 |
| outdoor | 7 |
| mind_body | 4 |
| other | 7 |

---

## RPG Professions per Category

Each category has a thematic description and 3 RPG professions. Stats use three attributes:
- **STR** (Strength) -- raw physical power
- **DEX** (Dexterity) -- speed, agility, coordination
- **CON** (Constitution) -- endurance, stamina, resilience

---

### 1. Combat

> The arena echoes with the clash of steel and the crack of fists against shields. Those who walk the path of combat live by the blade, the fist, and the iron will to stand when others fall. Every bruise is a lesson; every victory, a legend forged in sweat and blood.

| name | description | primary_stat | secondary_stat |
|------|-------------|-------------|----------------|
| **Gladiator** | A battle-hardened warrior who thrives in close-quarters combat. Every punch thrown and every blow absorbed transforms raw aggression into honed martial skill. The Gladiator does not merely fight -- they perform, turning violence into art. | STR | CON |
| **Shadow Striker** | A swift and precise combatant who favors speed over brute force. Trained in the art of kickboxing and martial techniques, the Shadow Striker weaves between attacks, landing devastating counters before vanishing from sight. | DEX | STR |
| **War Monk** | A disciplined fighter who channels inner calm into explosive power. Blending the philosophy of martial arts with the relentless intensity of combat drills, the War Monk strikes with purpose and defends with unwavering resolve. | CON | STR |

---

### 2. Running

> The open road calls to those with restless spirits and tireless legs. Runners are the scouts, the messengers, the first to arrive and the last to be caught. They chase the horizon itself, turning distance into their greatest weapon and endurance into their strongest armor.

| name | description | primary_stat | secondary_stat |
|------|-------------|-------------|----------------|
| **Wind Rider** | A runner so fast they seem to ride the wind itself. Sprints, intervals, and track work have forged legs that blur the line between athlete and force of nature. The Wind Rider lives for the rush of pure speed. | DEX | CON |
| **Pathfinder** | A tireless long-distance runner who maps the uncharted miles. Whether on treadmill or trail, the Pathfinder finds rhythm in repetition and strength in the steady drumbeat of footfalls. No distance is too far, no route unknown. | CON | DEX |
| **Rogue Scout** | A cunning runner who uses speed as both weapon and escape. Trained in quick bursts and sudden changes of direction, the Rogue Scout is impossible to pin down, darting through any terrain with predatory agility. | DEX | STR |

---

### 3. Walking

> Not every journey demands haste. The walkers and hikers know that true strength is found in the patient step, the long climb, and the quiet endurance of one foot in front of the other. Mountains bow to those who refuse to stop ascending.

| name | description | primary_stat | secondary_stat |
|------|-------------|-------------|----------------|
| **Wayfarer** | A seasoned traveler who has walked more miles than most dare to dream. The Wayfarer's endurance is legendary -- storms, slopes, and endless roads only sharpen their resolve. They carry the wisdom of a thousand trails. | CON | DEX |
| **Summit Warden** | A specialist in vertical challenges -- stairs, hills, and mountain paths are their domain. The Summit Warden ascends where others turn back, drawing power from altitude and the burn of an endless climb. | CON | STR |
| **Pilgrim** | A contemplative walker whose journey is as much spiritual as physical. Every step is deliberate, every mile a meditation. The Pilgrim transforms the simple act of walking into an engine of quiet, relentless transformation. | CON | DEX |

---

### 4. Cycling

> Steel frames and spinning wheels carry the cyclists into battle against wind, gravity, and their own limits. Whether racing along open roads or grinding through stationary intervals, cyclists forge legs of iron and a heart that refuses to quit.

| name | description | primary_stat | secondary_stat |
|------|-------------|-------------|----------------|
| **Iron Rider** | A road warrior who dominates long distances on two wheels. The Iron Rider's legs are pistons of endurance, churning through miles with mechanical precision. Hills are merely invitations to push harder. | CON | STR |
| **Cyclone** | An explosive cyclist built for speed and power. Whether sprinting on a track or hammering intervals on a stationary bike, the Cyclone generates raw kinetic force that leaves rivals spinning in their wake. | STR | DEX |
| **Chain Warden** | A versatile cyclist who adapts to any terrain or machine. From hand cycling to mountain trails, the Chain Warden masters every form of pedal-powered movement, treating each ride as a link in an unbreakable chain of progress. | CON | DEX |

---

### 5. Swimming

> Beneath the surface lies a world where gravity surrenders and the body moves through liquid resistance. Swimmers are amphibious warriors -- part athlete, part creature of the deep. Every stroke carves strength from the water itself.

| name | description | primary_stat | secondary_stat |
|------|-------------|-------------|----------------|
| **Tide Walker** | A powerful swimmer who commands the water with graceful, relentless strokes. Whether in pool or open sea, the Tide Walker moves through the depths as naturally as breathing air. The current is not an obstacle -- it is an ally. | CON | STR |
| **Deep Diver** | A specialist in underwater endurance and aquatic fitness. The Deep Diver thrives where oxygen is scarce and pressure mounts, training the body to perform at its peak in the most demanding aquatic environments. | CON | DEX |
| **Leviathan** | A raw-power swimmer who treats the water as a battlefield. Water polo, competitive laps, open-water crossings -- the Leviathan attacks every aquatic challenge with brute force and an indomitable will that refuses to sink. | STR | CON |

---

### 6. Strength

> Iron bends before those who lift it long enough. The strength disciples worship at the altar of the barbell, forging bodies of steel through progressive overload and sheer, stubborn repetition. They are the titans who reshape themselves one rep at a time.

| name | description | primary_stat | secondary_stat |
|------|-------------|-------------|----------------|
| **Berserker** | A raw-power lifter who channels fury into every rep. The Berserker does not merely lift weights -- they conquer them. Heavy iron, explosive sets, and an appetite for personal records define this unstoppable force. | STR | CON |
| **Titan** | A master of structured strength training who builds power methodically. The Titan follows the ancient discipline of progressive overload, constructing a body that is both fortress and weapon through patient, calculated effort. | STR | CON |
| **Iron Golem** | A functional-strength specialist whose power is practical, not performative. Calisthenics, core work, and unconventional training forge the Iron Golem into a machine that is as capable in the real world as in the gym. | STR | DEX |

---

### 7. Flexibility

> Where the rigid break, the flexible endure. Practitioners of the bending arts cultivate bodies that flow like water and minds as still as stone. Yoga, pilates, and the ancient disciplines of balance teach that true power lies in control, not force.

| name | description | primary_stat | secondary_stat |
|------|-------------|-------------|----------------|
| **Serpent Monk** | A yoga and flexibility master who has achieved near-supernatural control over their body. The Serpent Monk bends without breaking, holds positions that defy anatomy, and channels stillness into explosive potential. | DEX | CON |
| **Silk Dancer** | A pilates and barre specialist who moves with effortless precision. Every controlled movement builds invisible strength, and the Silk Dancer's graceful exterior conceals a core of tempered steel. | DEX | STR |
| **Jade Sage** | A tai chi practitioner and gymnast who has mastered the balance between motion and stillness. The Jade Sage moves like wind through bamboo -- fluid, deliberate, and utterly in command of every fiber of their being. | DEX | CON |

---

### 8. Cardio

> The heart is the engine; cardio is the fuel. HIIT warriors, elliptical grinders, and cross-trainers push their cardiovascular systems to the bleeding edge, building an engine that outlasts all comers. When others gasp, cardio fighters are just warming up.

| name | description | primary_stat | secondary_stat |
|------|-------------|-------------|----------------|
| **Storm Runner** | A HIIT specialist who thrives in the chaos of interval training. Short, explosive bursts followed by relentless recovery cycles have forged the Storm Runner into a metabolic powerhouse that strikes fast and recovers faster. | DEX | CON |
| **Forge Master** | A cross-training generalist who tempers the body through diverse, punishing cardio workouts. The Forge Master treats every machine and every exercise as a different hammer, shaping endurance into something unbreakable. | CON | STR |
| **Pulse Warden** | A step-training and elliptical specialist who turns monotonous repetition into meditative endurance. The Pulse Warden's heart rate is a metronome of controlled power, never spiking too high, never dropping too low. | CON | DEX |

---

### 9. Dance

> The battlefield of rhythm demands both body and soul. Dancers are the bards of the physical world -- their movements tell stories, inspire allies, and transform sweat into spectacle. To dance is to fight gravity with joy.

| name | description | primary_stat | secondary_stat |
|------|-------------|-------------|----------------|
| **War Bard** | A cardio dancer who turns high-energy routines into combat-ready conditioning. The War Bard's movements are explosive, rhythmic, and exhausting, blurring the line between performance and physical domination. | DEX | CON |
| **Phantom Step** | A social and ballroom dancer whose footwork is so precise it borders on supernatural. The Phantom Step glides across any floor with effortless coordination, turning every dance into a display of agility and control. | DEX | STR |
| **Rhythm Shaman** | A dancer who channels the primal beat of music into physical transformation. Whether freestyle or choreographed, the Rhythm Shaman moves with wild, instinctive grace that builds endurance and coordination simultaneously. | DEX | CON |

---

### 10. Winter Sports

> Ice and snow forge warriors of a different kind. The frozen wastes demand balance, explosiveness, and an iron tolerance for cold and discomfort. Those who conquer winter conquer one of nature's harshest proving grounds.

| name | description | primary_stat | secondary_stat |
|------|-------------|-------------|----------------|
| **Frost Ranger** | A cross-country skier and snowshoer who patrols the frozen wilderness. The Frost Ranger's endurance is forged in subzero conditions, covering vast snowy distances with tireless, rhythmic efficiency. | CON | DEX |
| **Avalanche Knight** | A downhill skier and snowboarder who charges down mountains at terminal velocity. The Avalanche Knight thrives on speed, gravity, and the razor-thin margin between control and catastrophe. | DEX | STR |
| **Ice Sentinel** | A skating and curling specialist whose balance and precision on ice are unmatched. The Ice Sentinel moves across frozen surfaces with supernatural grace, turning the most treacherous terrain into a personal arena. | DEX | CON |

---

### 11. Racquet Sports

> The court is a chessboard, and the racquet is a sword. Racquet athletes combine explosive speed, surgical precision, and tactical cunning into a discipline that punishes the slow and rewards the sharp. Every rally is a duel.

| name | description | primary_stat | secondary_stat |
|------|-------------|-------------|----------------|
| **Blade Dancer** | A tennis and squash specialist who wields a racquet with the precision of a fencer. Quick reflexes, devastating serves, and relentless court coverage make the Blade Dancer a nightmare for any opponent. | DEX | STR |
| **Thunder Ace** | A power-focused racquet player who dominates with raw speed and crushing shots. Whether smashing a shuttlecock or blasting a tennis serve, the Thunder Ace ends rallies with authority. | STR | DEX |
| **Net Phantom** | A table tennis and pickleball specialist whose reflexes operate at near-precognitive speed. The Net Phantom reads angles before they exist and returns shots that should be impossible, turning defense into instant offense. | DEX | CON |

---

### 12. Team Sports

> No warrior stands truly alone. The team sports arena demands communication, sacrifice, and the willingness to subordinate ego for collective glory. Here, tacticians command the field and every player is both sword and shield.

| name | description | primary_stat | secondary_stat |
|------|-------------|-------------|----------------|
| **Field Marshal** | A soccer, football, or rugby player who commands the pitch through positioning and game intelligence. The Field Marshal reads the battlefield, directs allies, and strikes at the decisive moment. | STR | CON |
| **Shield Brother** | A basketball, volleyball, or handball player built for explosive teamwork. The Shield Brother protects, passes, and attacks as one unified force, drawing strength from the synergy of coordinated play. | STR | DEX |
| **Battle Tactician** | A cricket, baseball, or hockey strategist who combines patience with devastating bursts of action. The Battle Tactician waits for the perfect moment, then executes with calculated, lethal precision. | DEX | CON |

---

### 13. Water Sports

> The sea is the ultimate testing ground. Rowers, sailors, surfers, and paddlers face a force that cannot be tamed, only respected and harnessed. Those who thrive on the water carry a strength shaped by tides and carved by waves.

| name | description | primary_stat | secondary_stat |
|------|-------------|-------------|----------------|
| **Storm Sailor** | A sailing and surfing specialist who reads wind and wave like an ancient mariner. The Storm Sailor harnesses natural forces rather than fighting them, turning raw ocean power into forward momentum. | DEX | CON |
| **Oar Warden** | A rowing and paddle sports powerhouse whose back and arms are forged by countless strokes against resistance. The Oar Warden transforms repetitive pulling into unstoppable aquatic propulsion. | STR | CON |
| **Abyss Walker** | A scuba diver and underwater explorer who ventures where sunlight fails. The Abyss Walker's calm under pressure and mastery of breath control make them fearless in the deep, where lesser adventurers dare not descend. | CON | DEX |

---

### 14. Outdoor

> The great outdoors is the original gymnasium. Climbers, archers, riders, and anglers face challenges no machine can replicate. Nature provides the resistance; the adventurer provides the will.

| name | description | primary_stat | secondary_stat |
|------|-------------|-------------|----------------|
| **Crag Sentinel** | A rock climber and mountaineer who conquers vertical worlds. The Crag Sentinel's grip strength is legendary, their spatial awareness preternatural, and their comfort with exposure total. The wall is not an obstacle -- it is home. | STR | DEX |
| **Hawk Eye** | An archer, golfer, and precision-sport specialist who turns focus into physical mastery. The Hawk Eye's concentration is absolute, their technique refined through thousands of repetitions until every shot is instinct. | DEX | CON |
| **Beast Rider** | An equestrian and outdoor adventurer who bonds with the wild. The Beast Rider combines physical endurance with an intuitive connection to nature, thriving in environments that test both body and spirit. | CON | STR |

---

### 15. Mind & Body

> The greatest battles are fought within. Mind-body practitioners harness the invisible forces of breath, focus, and inner stillness, transforming meditation into a weapon and recovery into a superpower. The body follows where the mind leads.

| name | description | primary_stat | secondary_stat |
|------|-------------|-------------|----------------|
| **Zen Oracle** | A meditation and guided-breathing master who has achieved dominion over the autonomic self. The Zen Oracle's resting heart rate is a whisper, their recovery supernatural, and their focus an unbreakable blade of calm. | CON | DEX |
| **Spirit Weaver** | A mind-and-body practitioner who integrates mental discipline with physical conditioning. The Spirit Weaver's cooldown routines and preparatory rituals are not afterthoughts -- they are the foundation upon which all other training stands. | CON | DEX |
| **Void Walker** | A practitioner of deep recovery and preparation arts who turns rest into a weapon. The Void Walker understands that growth happens in stillness, and masters the art of rebuilding the body between battles. | CON | STR |

---

### 16. Other

> Not all heroes fit neatly into a guild. The misfits, the innovators, and the playful spirits forge their own path through unconventional training. Whether rolling a bowling ball, gaming with motion controls, or wheeling through an obstacle course, they prove that fitness has no single form.

| name | description | primary_stat | secondary_stat |
|------|-------------|-------------|----------------|
| **Wild Card** | An unpredictable fitness enthusiast who draws power from variety and surprise. The Wild Card refuses to specialize, instead sampling every discipline and turning versatility itself into a devastating advantage. | DEX | CON |
| **Tinkerer** | A fitness-gaming and unconventional-training specialist who turns play into progress. The Tinkerer proves that joy and gains are not mutually exclusive, building real strength through creative, unorthodox methods. | DEX | STR |
| **Iron Wheel** | A wheelchair athlete whose upper-body power and rolling endurance defy all limitations. The Iron Wheel transforms the chair from a constraint into a chariot, attacking every course and every challenge with ferocious determination. | STR | CON |

---

## Quick Reference: Category-to-Profession Map

| Category | Profession 1 | Profession 2 | Profession 3 |
|----------|--------------|--------------|--------------|
| combat | Gladiator | Shadow Striker | War Monk |
| running | Wind Rider | Pathfinder | Rogue Scout |
| walking | Wayfarer | Summit Warden | Pilgrim |
| cycling | Iron Rider | Cyclone | Chain Warden |
| swimming | Tide Walker | Deep Diver | Leviathan |
| strength | Berserker | Titan | Iron Golem |
| flexibility | Serpent Monk | Silk Dancer | Jade Sage |
| cardio | Storm Runner | Forge Master | Pulse Warden |
| dance | War Bard | Phantom Step | Rhythm Shaman |
| winter_sports | Frost Ranger | Avalanche Knight | Ice Sentinel |
| racquet_sports | Blade Dancer | Thunder Ace | Net Phantom |
| team_sports | Field Marshal | Shield Brother | Battle Tactician |
| water_sports | Storm Sailor | Oar Warden | Abyss Walker |
| outdoor | Crag Sentinel | Hawk Eye | Beast Rider |
| mind_body | Zen Oracle | Spirit Weaver | Void Walker |
| other | Wild Card | Tinkerer | Iron Wheel |

---

## Quick Reference: Profession Stats Summary

| Profession | Category | Primary | Secondary |
|------------|----------|---------|-----------|
| Gladiator | combat | STR | CON |
| Shadow Striker | combat | DEX | STR |
| War Monk | combat | CON | STR |
| Wind Rider | running | DEX | CON |
| Pathfinder | running | CON | DEX |
| Rogue Scout | running | DEX | STR |
| Wayfarer | walking | CON | DEX |
| Summit Warden | walking | CON | STR |
| Pilgrim | walking | CON | DEX |
| Iron Rider | cycling | CON | STR |
| Cyclone | cycling | STR | DEX |
| Chain Warden | cycling | CON | DEX |
| Tide Walker | swimming | CON | STR |
| Deep Diver | swimming | CON | DEX |
| Leviathan | swimming | STR | CON |
| Berserker | strength | STR | CON |
| Titan | strength | STR | CON |
| Iron Golem | strength | STR | DEX |
| Serpent Monk | flexibility | DEX | CON |
| Silk Dancer | flexibility | DEX | STR |
| Jade Sage | flexibility | DEX | CON |
| Storm Runner | cardio | DEX | CON |
| Forge Master | cardio | CON | STR |
| Pulse Warden | cardio | CON | DEX |
| War Bard | dance | DEX | CON |
| Phantom Step | dance | DEX | STR |
| Rhythm Shaman | dance | DEX | CON |
| Frost Ranger | winter_sports | CON | DEX |
| Avalanche Knight | winter_sports | DEX | STR |
| Ice Sentinel | winter_sports | DEX | CON |
| Blade Dancer | racquet_sports | DEX | STR |
| Thunder Ace | racquet_sports | STR | DEX |
| Net Phantom | racquet_sports | DEX | CON |
| Field Marshal | team_sports | STR | CON |
| Shield Brother | team_sports | STR | DEX |
| Battle Tactician | team_sports | DEX | CON |
| Storm Sailor | water_sports | DEX | CON |
| Oar Warden | water_sports | STR | CON |
| Abyss Walker | water_sports | CON | DEX |
| Crag Sentinel | outdoor | STR | DEX |
| Hawk Eye | outdoor | DEX | CON |
| Beast Rider | outdoor | CON | STR |
| Zen Oracle | mind_body | CON | DEX |
| Spirit Weaver | mind_body | CON | DEX |
| Void Walker | mind_body | CON | STR |
| Wild Card | other | DEX | CON |
| Tinkerer | other | DEX | STR |
| Iron Wheel | other | STR | CON |
