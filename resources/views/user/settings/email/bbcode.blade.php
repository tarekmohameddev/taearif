<div class="col-lg-5">
    <table class="table table-striped" style="border: 1px solid #0000005a;">
        <thead>
            <tr>
                <th scope="col">{{ __('Short Code') }}</th>
                <th scope="col">{{ __('Meaning') }}</th>
            </tr>
        </thead>
        <tbody>
            @if ($templateInfo->email_type == 'donation_approved' || $templateInfo->email_type == 'donation')
                <tr>
                    <td>{donor_name}</td>
                    <td scope="row">{{ __('Name of The Donor') }}</td>
                </tr>
                 <tr>
                    <td>{cause_name}</td>
                    <td scope="row">{{ __('Name of The Cause') }}</td>
                </tr>
            @else
                <tr>
                    <td>{customer_name}</td>
                    <td scope="row">{{ __('Name of The Customer') }}</td>
                </tr>
            @endif
            @if ($templateInfo->email_type == 'email_verification')
                <tr>
                    <td>{verification_link}</td>
                    <td scope="row">{{ __('Email Verification Link') }}</td>
                </tr>
            @endif
            @if ($templateInfo->email_type == 'reset_password')
                <tr>
                    <td>{password_reset_link}</td>
                    <td scope="row">{{ __('Password Reset Link') }}</td>
                </tr>
            @endif
            <tr>
                <td>{website_title}</td>
                <td scope="row">{{ __('Website Title') }}</td>
            </tr>
            @if ($templateInfo->email_type == 'room_booking')
                <tr>
                    <td>
                        {booking_number}
                    </td>
                    <th scope="row">
                        {{ __('Booking Number') }}
                    </th>
                </tr>
                <tr>
                    <td>
                        {booking_date}
                    </td>
                    <th scope="row">
                        {{ __('Booking Date') }}
                    </th>
                </tr>
                <tr>
                    <td>
                        {number_of_night}
                    </td>
                    <th scope="row">
                        {{ __('Number of Nights') }}
                    </th>
                </tr>
                <tr>
                    <td>
                        {check_in_date}
                    </td>
                    <th scope="row">
                        {{ __('Check in Date') }}
                    </th>
                </tr>
                <tr>
                    <td>
                        {check_out_date}
                    </td>
                    <th scope="row">
                        {{ __('Check out Date') }}
                    </th>
                </tr>
                <tr>
                    <td>
                        {number_of_guests}
                    </td>
                    <th scope="row">
                        {{ __('Number of Guests') }}
                    </th>
                </tr>
                <tr>
                    <td>
                        {room_name}
                    </td>
                    <th scope="row">
                        {{ __('Room Name') }}
                    </th>
                </tr>
                <tr>
                    <td>
                        {room_rent}
                    </td>
                    <th scope="row">
                        {{ __('Room Rent') }}
                    </th>
                </tr>
                <tr>
                    <td>
                        {room_type}
                    </td>
                    <th scope="row">
                        {{ __('Room Type') }}
                    </th>
                </tr>
                <tr>
                    <td>
                        {room_amenities}
                    </td>
                    <th scope="row">
                        {{ __('Room Amenities') }}
                    </th>
                </tr>
            @endif
        </tbody>
    </table>
</div>
