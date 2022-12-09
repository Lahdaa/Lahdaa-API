<table width="700" border="0" align="center" cellpadding="0" cellspacing="0" style="background:#fff;color:#333;font-size:14px;padding:20px;">
  <tbody>
    <tr>
      <td>
        <p> Dear {{$user_fullName}}, </p>
        <p>Thank you for buying the course titled: <b>{{$course_name}}</b>. </p>
        <p> Your transaction details are: </p>
        <table>
          <tbody>
            <tr>
              <th colspan="2" align="center">Student Details</th>
            </tr>
            <tr>
              <td>
                <strong>Name:</strong>
              </td>
              <td>{{$user_fullName}}</td>
            </tr>
            <tr>
              <td>
                <strong>Phone Number:</strong>
              </td>
              <td>{{$user_phoneNumber}}</td>
            </tr>
            <tr>
              <td>
                <strong>Email:</strong>
              </td>
              <td>{{$user_email}}</td>
            </tr>
            <tr>
              <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
              <th colspan="2" align="center">Payment Details</th>
            </tr>
            <tr>
              <td>
                <strong>Payment Reference:</strong>
              </td>
              <td>{{$tnx_ref}}</td>
            </tr>
            <tr>
              <td>
                <strong>Amount Paid:</strong>
              </td>
              <td> {{$amount_currency}} {{$amount}}</td>
            </tr>
            <tr>
              <td>
                <strong>Channel:</strong>
              </td>
              <td>{{$channel}}</td>
            </tr>
            <tr>
              <td>
                <strong>Response:</strong>
              </td>
              <td>Transaction Successful</td>
            </tr>
            <tr>
              <td>
                <strong>Date:</strong>
              </td>
              <td>{{$date_created}}</td>
            </tr>
          </tbody>
        </table>
        <p>
          <em>Thank you for choosing <strong>Stevia Pro</strong>. </em>
        </p>
        <p>Best Regards,</p>
        <p>Stevia</p>
      </td>
    </tr>
  </tbody>
</table>