Common Feature Types To Implement:

1. Bet Management

    - [ ] Place bets
    - [ ] Cancel bets
    - [ ] View bet history

2. Wallet Operations
    - [ ] Process deposits
    - [ ] Process withdrawals
    - [ ] Track balances
3. Game Results

    - [ ] Generate results
    - [ ] Validate results
    - [ ] Display results

4. Reporting

    - [ ] Generate analytics
    - [ ] Create reports
    - [ ] Export data

5. Notifications

    - [ ] Send real-time updates
    - [ ] Manage notification preferences
    - [ ] Track notification history

6. Agent Management
    - [ ] Setup hierarchical structure
    - [ ] Manage agent relationships
    - [ ] Configure agent settings

Each feature will include:

-   [ ] Clean Architecture implementation
-   [ ] Comprehensive test coverage
-   [ ] Database migrations
-   [ ] API endpoint definitions
-   [ ] Service container bindings

# Featuer Details

6. Agent Management
   The Agent is user (Use to login to system), they have hierachy using upline_id.

    - Index (view) agent feature: In UI we want the agnet table showing the all agents that are first downline if login agent is Master it should show all it's downline. eg. AAAAAA (agent_type === master), should see AAAAAAAA (agent_type === agent), AAAAAAAB (agent_type === agent), AAAAAAAC (agent_type === agent). All agents should use be like this, and if they are click on those agent username they will navigate to drill down to thire downline, eg. that master click on AAAAAAAC it show all AAAAAAAC's downline.
    - Create agent features: Each agent can create every new agent that under them only, eg. that master AAAAAA, can create new agent (agent_type === agent) username AAAAAAAD, or AAAAAAAE, and also can create new member under that agent too, eg. agent (agent_type === member) username AAAAAAAD000 under agent (agent_type === agent) username AAAAAAAD.

    * Important all agent (user) username should follow the username rule, it go like this,

    - Agent type company should have username only 1 char, eg. A, B, C, D, ..., Z.
    - Agent type super senior should have username only 2 chars, eg. AA, AB, BA, CA, DD, ..., ZZ.
    - Agent type senior should have username only 4 chars, eg. AAAA, ABAA, BAAA, CAAA, DDDA, ..., ZZZZ.
    - Agent type master should have username only 6 chars, eg. AAAAAA, ABAABA, BAAAAA, CACADA, DDDDDA, ..., ZZZZZZ.
    - Agent type agent should have username only 8 chars, eg. AAAAAAAA, ABAABAAA, BAAAAAAA, CACADADD, DDDDDAZA, ..., ZZZZZZZZ.
    - Agent type agent should have username only 8 chars + 3 ditgits number, eg. AAAAAAAA000, ABAABAAA021, BAAAAAAA001, CACADADD002, DDDDDAZA001, ..., ZZZZZZZZ999.

    As this username rule they can know the upline by thire username too, eg. Company A username A, all thire downline should start with A, Master ABSAAA all thire downline should start with ABSAAA, like agent ABSAAABB is downline of master ABSAAA, agent ABSAAABC is downline of master ABSAAA, so not only using upline_id we also use this username too to identify the hierachy. It not important to use in system and developement phasse because using this kind of username in hierachy system can causse slow query, but this use in real bussiness to identify the agent upline and downline, so we need it.

    Note that From Company to Agent is for login to same admin dashboard, only Member level that separate frontend use to place order betting.
