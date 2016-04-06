# Secrutiry

This bundle enable to pull, edit and push data into your database. 
it is very handy for an admin zone but you do NOT want it io be accessible to everyone !
So please add the in the security layer a way to nake sure thoses routes are avaible only to the right users

>
    access_control:
        - { path: ^/api, roles: [ IS_AUTHENTICATED_FULLY ] }
